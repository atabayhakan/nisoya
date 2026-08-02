<?php

namespace Tests\Feature;

use App\Ai\Kahya\Araclar\TabloSorgula;
use App\Ai\Kahya\EylemToplayici;
use App\Ai\Kahya\KahyaAjani;
use App\Ai\Kahya\YonlendirmeToplayici;
use App\Enums\HafizaTuru;
use App\Enums\UserRole;
use App\Models\KahyaEylemKaydi;
use App\Models\KahyaHafizasi;
use App\Models\User;
use App\Services\Kahya\BekleyenIsler;
use App\Services\Kahya\Eylem\EylemCalistirici;
use App\Services\Kahya\Eylem\EylemKatalogu;
use App\Services\Kahya\KahyaTeshisi;
use App\Services\Kahya\PanelHaritasi;
use App\Services\Kahya\Sohbet\KahyaSohbeti;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Ai;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

/**
 * Kâhya'nın kalıcı hafızası (F1 — tasarım §2.3).
 *
 * Sınanan sözleşme: sohbetten yazılan hafıza gerçekten kalıcı mı, "unut"
 * gerçekten geri alınabilir mi, yönerge doğru kayıtları taşıyor mu ve
 * arama sayacı yalnız ARANANI mı sayıyor.
 */
class KahyaHafizaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    private function calistirici(): EylemCalistirici
    {
        return app(EylemCalistirici::class);
    }

    private function ajan(): KahyaAjani
    {
        return new KahyaAjani(
            app(KahyaTeshisi::class),
            app(BekleyenIsler::class),
            app(PanelHaritasi::class),
            app(EylemKatalogu::class),
            app(EylemCalistirici::class),
            new EylemToplayici,
            new YonlendirmeToplayici,
            collect(),
        );
    }

    // ------------------------------------------------------- hatirla eylemi

    public function test_hatirla_kayit_acar_geri_alma_siler(): void
    {
        $kayit = $this->calistirici()->calistir('hatirla', [
            'metin' => 'Kategori eklerken hep ikon da koy.',
            'tur' => 'kural',
        ]);

        $this->assertSame(KahyaEylemKaydi::DURUM_UYGULANDI, $kayit->durum);
        $this->assertDatabaseHas('kahya_hafiza', [
            'metin' => 'Kategori eklerken hep ikon da koy.',
            'tur' => 'kural',
            'kaynak' => KahyaHafizasi::KAYNAK_SAHIP,
            'aktif' => true,
        ]);

        $this->calistirici()->geriAl($kayit);

        $this->assertSame(0, KahyaHafizasi::query()->count());
    }

    public function test_hatirla_tur_verilmezse_not_olur(): void
    {
        $this->calistirici()->calistir('hatirla', ['metin' => 'Serbest bir bilgi notu.']);

        $this->assertSame(HafizaTuru::Not, KahyaHafizasi::query()->firstOrFail()->tur);
    }

    // ---------------------------------------------------------- unut eylemi

    public function test_unut_pasife_ceker_geri_alma_aktifler(): void
    {
        $hafiza = KahyaHafizasi::create(['tur' => HafizaTuru::Kural, 'metin' => 'Eski kural burada duruyor.']);

        $kayit = $this->calistirici()->calistir('unut', ['id' => $hafiza->id]);

        $this->assertSame(KahyaEylemKaydi::DURUM_UYGULANDI, $kayit->durum);
        // Silinmedi — pasife çekildi: "unut" da geri alınabilir olmalı.
        $this->assertFalse($hafiza->refresh()->aktif);

        $this->calistirici()->geriAl($kayit);

        $this->assertTrue($hafiza->refresh()->aktif);
    }

    public function test_unut_zaten_pasif_kaydi_geri_alinca_aktiflemez(): void
    {
        $hafiza = KahyaHafizasi::create(['tur' => HafizaTuru::Not, 'metin' => 'Zaten pasif bir kayıt.', 'aktif' => false]);

        $kayit = $this->calistirici()->calistir('unut', ['id' => $hafiza->id]);
        $this->calistirici()->geriAl($kayit);

        // Geri alma "unutmayı" geri alır, kaydı DİRİLTMEZ.
        $this->assertFalse($hafiza->refresh()->aktif);
    }

    public function test_unut_bilinmeyen_id_dogrulamada_reddedilir(): void
    {
        $kayit = $this->calistirici()->calistir('unut', ['id' => 999999]);

        $this->assertSame(KahyaEylemKaydi::DURUM_HATA, $kayit->durum);
    }

    // ------------------------------------------------------------- Yönerge

    public function test_yonerge_aktif_kayitlari_tasir_pasifleri_tasimaz(): void
    {
        KahyaHafizasi::create(['tur' => HafizaTuru::Kural, 'metin' => 'SEO metnini önce bana göster.']);
        KahyaHafizasi::create(['tur' => HafizaTuru::Not, 'metin' => 'Bu pasif kayıt görünmemeli.', 'aktif' => false]);

        $yonerge = (string) $this->ajan()->instructions();

        $this->assertStringContainsString('Hatırladıkların', $yonerge);
        $this->assertStringContainsString('SEO metnini önce bana göster.', $yonerge);
        $this->assertStringContainsString('Kural]', $yonerge);
        $this->assertStringNotContainsString('Bu pasif kayıt görünmemeli.', $yonerge);
    }

    public function test_yonerge_bos_hafizada_hatirla_yonlendirmesi_yapar(): void
    {
        $yonerge = (string) $this->ajan()->instructions();

        $this->assertStringContainsString('Hafızan henüz boş', $yonerge);
    }

    /** Tavan aşımında çekirdek (kural/gerçek) kazanır, ders/not taşar. */
    public function test_yonerge_tavaninda_cekirdek_oncelikli(): void
    {
        for ($i = 1; $i <= 48; $i++) {
            KahyaHafizasi::create(['tur' => HafizaTuru::Kural, 'metin' => "Kural numara {$i} — dolgu metni."]);
        }
        for ($i = 1; $i <= 10; $i++) {
            KahyaHafizasi::create(['tur' => HafizaTuru::Not, 'metin' => "Not numara {$i} — dolgu metni."]);
        }

        $secilen = KahyaHafizasi::yonergeIcin(50);

        $this->assertCount(50, $secilen);
        $this->assertSame(48, $secilen->filter(fn (KahyaHafizasi $k): bool => $k->tur === HafizaTuru::Kural)->count());
        $this->assertSame(2, $secilen->filter(fn (KahyaHafizasi $k): bool => $k->tur === HafizaTuru::Not)->count());
    }

    // ----------------------------------------------- tablo-sorgula ile arama

    public function test_tablo_sorgula_hafizada_arar_ve_sayaci_artirir(): void
    {
        $hafiza = KahyaHafizasi::create(['tur' => HafizaTuru::Gercek, 'metin' => 'Hedef kitle önce Avrupa pazarı.']);
        KahyaHafizasi::create(['tur' => HafizaTuru::Not, 'metin' => 'Alakasız başka bir kayıt.']);

        $sonuc = (string) (new TabloSorgula)->handle(new Request([
            'tablo' => 'kahya_hafiza',
            'ara' => 'Avrupa',
        ]));

        $this->assertStringContainsString('Avrupa', $sonuc);
        $this->assertStringNotContainsString('Alakasız', $sonuc);
        // Sayaç yalnız BULUNANDA artar — yönerge enjeksiyonu sayılmaz.
        $this->assertSame(1, $hafiza->refresh()->kullanim_sayisi);
    }

    // ------------------------------------------------------ Uçtan uca sohbet

    public function test_sohbetten_hatirla_ucta_uca(): void
    {
        Ai::fakeAgent(KahyaAjani::class, [
            new ToolCall('t1', 'hatirla', ['metin' => 'Duyuru bandını cuma günleri güncelle.', 'tur' => 'kural']),
            'Hafızama yazdım.',
        ]);

        $yanit = app(KahyaSohbeti::class)->sor('şunu unutma: duyuru bandını cuma günleri güncelle', $this->admin());

        $this->assertNotNull($yanit->eylem);
        $this->assertSame(KahyaEylemKaydi::DURUM_UYGULANDI, $yanit->eylem->durum);
        $this->assertDatabaseHas('kahya_hafiza', ['tur' => 'kural', 'aktif' => true]);
    }

    // -------------------------------------------------------------- Arayüz

    public function test_hafiza_ekrani_yalniz_admin(): void
    {
        $this->actingAs($this->admin())
            ->get('/yonetim/kahya-hafiza')
            ->assertOk();

        $this->actingAs(User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]))
            ->get('/yonetim/kahya-hafiza')
            ->assertForbidden();
    }
}
