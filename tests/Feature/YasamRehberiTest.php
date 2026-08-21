<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\YasamKategorisi;
use App\Models\YasamKonuIcerigi;
use App\Models\YasamKonuOnerisi;
use App\Models\YasamKonusu;
use App\Services\Kahya\BekleyenIsler;
use App\Support\Settings;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Yaşam Rehberi (F0 altyapı) — bkz. docs/plans/2026-08-21-yasam-rehberi-tasarimi.md.
 *
 * Ülke Rehberi'nin RehberTest.php'siyle aynı iskelet: taslak-önce yayın
 * kapısı (K7), boş sayfa yalanı yok (K6), aynı modül anahtarı ("rehber")
 * paylaşıldığı için modül kapalıyken ikisi de kapanır.
 */
class YasamRehberiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    private function kategori(array $ekstra = []): YasamKategorisi
    {
        return YasamKategorisi::query()->create(array_merge([
            'ad' => 'Bankacılık & Finans',
            'slug' => 'bankacilik-finans',
            'ikon' => '🏦',
            'is_active' => true,
            'sort_order' => 1,
        ], $ekstra));
    }

    private function konu(YasamKategorisi $kategori, array $ekstra = []): YasamKonusu
    {
        return YasamKonusu::query()->create(array_merge([
            'kategori_id' => $kategori->id,
            'baslik' => "SSN'siz banka hesabı açma",
            'slug' => 'ssnsiz-hesap-acma',
            'kisa_aciklama' => 'Sosyal güvenlik numarası olmadan hesap açmak mümkün mü?',
            'is_active' => true,
            'sort_order' => 1,
        ], $ekstra));
    }

    private function yayindaIcerik(YasamKonusu $konu, string $ulke = 'DE', array $ekstra = []): YasamKonuIcerigi
    {
        return YasamKonuIcerigi::query()->create(array_merge([
            'yasam_konusu_id' => $konu->id,
            'country_code' => $ulke,
            'icerik' => [
                ['tip' => 'paragraf', 'metin' => 'Test içerik paragrafı.'],
                ['tip' => 'madde', 'metin' => 'Birinci madde.'],
                ['tip' => 'madde', 'metin' => 'İkinci madde.'],
            ],
            'kaynak_url' => 'https://example.com/kaynak',
            'kaynak_aciklama' => 'Resmî banka sayfası',
            'dogrulanma_tarihi' => now()->subDays(5),
            'status' => YasamKonuIcerigi::STATUS_YAYIN,
            'yazan_tur' => YasamKonuIcerigi::YAZAN_AI,
        ], $ekstra));
    }

    // ------------------------------------------------------------- Sayfalar

    public function test_kategori_listesi_yayindaki_kategorileri_gosterir(): void
    {
        $kategori = $this->kategori();
        $konu = $this->konu($kategori);
        $this->yayindaIcerik($konu);

        $this->get('/de/yasam')
            ->assertOk()
            ->assertSee('Bankacılık & Finans');
    }

    public function test_hic_yayinda_icerik_yoksa_hazirlaniyor_gorunur_404_degil(): void
    {
        $this->get('/de/yasam')
            ->assertOk()
            ->assertSee('henüz hazırlanıyor');
    }

    public function test_pasif_veya_bilinmeyen_ulke_404(): void
    {
        $this->get('/xx/yasam')->assertNotFound();
    }

    public function test_konu_listesi_yalniz_yayindaki_konulari_gosterir(): void
    {
        $kategori = $this->kategori();
        $yayindaKonu = $this->konu($kategori, ['slug' => 'yayinda-konu', 'baslik' => 'Yayındaki Konu']);
        $this->yayindaIcerik($yayindaKonu);

        $taslakKonu = $this->konu($kategori, ['slug' => 'taslak-konu', 'baslik' => 'Taslak Konu']);
        $this->yayindaIcerik($taslakKonu, 'DE', ['status' => YasamKonuIcerigi::STATUS_TASLAK]);

        $html = $this->get('/de/yasam/'.$kategori->slug)->assertOk()->getContent();

        $this->assertStringContainsString('Yayındaki Konu', $html);
        $this->assertStringNotContainsString('Taslak Konu', $html);
    }

    public function test_hic_yayinda_konu_yoksa_kategori_sayfasi_404(): void
    {
        $kategori = $this->kategori();
        $konu = $this->konu($kategori);
        $this->yayindaIcerik($konu, 'DE', ['status' => YasamKonuIcerigi::STATUS_TASLAK]);

        $this->get('/de/yasam/'.$kategori->slug)->assertNotFound();
    }

    /** K7'nin kalbi: doğrulanmamış/taslak içerik hiçbir koşulda yayına sızmaz. */
    public function test_taslak_icerik_sayfasi_404(): void
    {
        $kategori = $this->kategori();
        $konu = $this->konu($kategori);
        $this->yayindaIcerik($konu, 'DE', ['status' => YasamKonuIcerigi::STATUS_TASLAK]);

        $this->get('/de/yasam/'.$kategori->slug.'/'.$konu->slug)->assertNotFound();
    }

    public function test_yayinda_icerik_sayfasi_blok_govdesini_basar(): void
    {
        $kategori = $this->kategori();
        $konu = $this->konu($kategori);
        $this->yayindaIcerik($konu);

        $html = $this->get('/de/yasam/'.$kategori->slug.'/'.$konu->slug)->assertOk()->getContent();

        $this->assertStringContainsString('Test içerik paragrafı.', $html);
        $this->assertStringContainsString('Birinci madde.', $html);
        $this->assertStringContainsString('Resmî banka sayfası', $html);
        $this->assertStringContainsString('example.com/kaynak', $html);
    }

    public function test_baska_ulkede_yayinda_icerik_bu_ulkede_404(): void
    {
        $kategori = $this->kategori();
        $konu = $this->konu($kategori);
        $this->yayindaIcerik($konu, 'DE');

        $this->get('/nl/yasam/'.$kategori->slug.'/'.$konu->slug)->assertNotFound();
    }

    // ------------------------------------------------- Rota sırası (regresyon)

    /**
     * REGRESYON BEKÇİSİ. rehber.temsilcilik rotası ({ulke}/{temsilcilik},
     * desen [a-z0-9\-]+) yasam-rehberi rotalarından ÖNCE tanımlansaydı,
     * "yasam" literalini bir temsilcilik slug'ı sanıp 404 verirdi. Bu test
     * gerçek kategoriler sayfasının döndüğünü, sahte bir temsilcilik
     * 404'ünün değil, doğrular.
     */
    public function test_yasam_yolu_temsilcilik_rotasina_yakalanmiyor(): void
    {
        $kategori = $this->kategori();
        $konu = $this->konu($kategori);
        $this->yayindaIcerik($konu);

        $this->get('/de/yasam')
            ->assertOk()
            ->assertSee('Yaşam Rehberi');
    }

    // --------------------------------------------------- Ülke Rehberi köprüsü

    public function test_ulke_sayfasinda_yasam_rehberi_blogu_yayinda_icerik_varsa_gorunur(): void
    {
        $kategori = $this->kategori();
        $konu = $this->konu($kategori);
        $this->yayindaIcerik($konu);

        $this->get('/de')->assertOk()->assertSee('Yaşam Rehberi');
    }

    public function test_ulke_sayfasinda_yasam_rehberi_blogu_icerik_yoksa_gorunmez(): void
    {
        $html = $this->get('/de')->assertOk()->getContent();

        $this->assertStringNotContainsString('yasam-rehberi.kategoriler', $html);
    }

    // ------------------------------------------------------------- Modül

    public function test_modul_kapaliyken_yasam_rehberi_de_404(): void
    {
        Settings::setMany(['modul.rehber' => '0']);

        $this->get('/de/yasam')->assertNotFound();
    }

    // ------------------------------------------------------------- Kâhya

    public function test_bayat_yasam_icerigi_kahya_kuyruguna_duser(): void
    {
        $kategori = $this->kategori();
        $konu = $this->konu($kategori);
        $this->yayindaIcerik($konu, 'DE', [
            'dogrulanma_tarihi' => now()->subDays(YasamKonuIcerigi::BAYATLIK_GUN + 1),
        ]);

        $kuyruk = app(BekleyenIsler::class)->topla();

        $bayatKuyrugu = collect($kuyruk)->firstWhere('anahtar', 'yasam_bayat');
        $this->assertNotNull($bayatKuyrugu, 'yasam_bayat kuyruğu Kâhya listesinde yok.');
        $this->assertSame(1, $bayatKuyrugu['adet']);
    }

    public function test_bekleyen_yasam_onerisi_kahya_kuyruguna_duser(): void
    {
        $kategori = $this->kategori();
        $konu = $this->konu($kategori);
        $icerik = $this->yayindaIcerik($konu);
        $kullanici = User::factory()->create();

        YasamKonuOnerisi::query()->create([
            'yasam_konu_icerigi_id' => $icerik->id,
            'user_id' => $kullanici->id,
            'onerilen_metin' => 'Bu bilgi artık değişti, şöyle olmalı...',
            'durum' => YasamKonuOnerisi::DURUM_BEKLIYOR,
        ]);

        $kuyruk = app(BekleyenIsler::class)->topla();

        $oneriKuyrugu = collect($kuyruk)->firstWhere('anahtar', 'yasam_oneri_bekleyen');
        $this->assertNotNull($oneriKuyrugu, 'yasam_oneri_bekleyen kuyruğu Kâhya listesinde yok.');
        $this->assertSame(1, $oneriKuyrugu['adet']);
    }

    // ------------------------------------------------------- Yetkilendirme

    public function test_admin_panel_kaynaklari_erisilebilir(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/yonetim/yasam-kategorileri')->assertOk();
        $this->actingAs($admin)->get('/yonetim/yasam-konulari')->assertOk();
        $this->actingAs($admin)->get('/yonetim/yasam-konu-icerikleri')->assertOk();
        $this->actingAs($admin)->get('/yonetim/yasam-konu-onerileri')->assertOk();
    }

    public function test_moderator_yasam_rehberi_kaynaklarina_erisemez(): void
    {
        $moderator = User::factory()->create(['role' => 'moderator']);

        $this->actingAs($moderator)->get('/yonetim/yasam-kategorileri')->assertForbidden();
    }
}
