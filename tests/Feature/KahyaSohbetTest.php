<?php

namespace Tests\Feature;

use App\Ai\Kahya\EylemToplayici;
use App\Ai\Kahya\KahyaAjani;
use App\Enums\UserRole;
use App\Filament\Pages\KahyaSohbet;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\KahyaEylemKaydi;
use App\Models\KahyaMesaji;
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
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Ai;
use Laravel\Ai\Responses\Data\ToolCall;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Kâhya sohbeti — F0 "beyin nakli" sonrası: laravel/ai ajan döngüsü.
 *
 * Testler modelin ZEKÂSINI sınamaz (model sahte, kararı test yazar); kararın
 * ETRAFINI sınar. Sahte gateway'in güzelliği: `ToolCall` yanıtı verildiğinde
 * ajan döngüsü GERÇEK aracı çalıştırır — eski düzenden farklı olarak
 * doğrulama, denetim defteri ve geri-alma izi testte de gerçek yoldan akar.
 *
 * NOT — "json kelimesi" mezar taşı testi buradan KALKTI (2026-07-30):
 * o test `response_format:json_object` hilesinin HTTP kısıtını korurdu;
 * F0'da Kâhya native tool-calling'e geçti ve o hile bu yoldan tamamen
 * çıktı. Aynı tehlike ImageModerationService'te sürüyor ve oradaki test
 * yerinde duruyor. Kâhya tarafındaki gerçek-sağlayıcı güvencesi artık
 * deploy öncesi yerel smoke testidir (tasarım belgesi §5).
 */
class KahyaSohbetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'name' => 'Hakan Atabay', 'email_verified_at' => now()]);
    }

    private function sohbet(): KahyaSohbeti
    {
        return app(KahyaSohbeti::class);
    }

    /** @param  list<ToolCall|string>  $yanitlar */
    private function sahteAjan(array $yanitlar): void
    {
        Ai::fakeAgent(KahyaAjani::class, $yanitlar);
    }

    // ----------------------------------------------------------- Karşılama

    public function test_karsilama_selam_ve_bekleyen_isleri_icerir(): void
    {
        ContactMessage::create([
            'name' => 'Ziyaretçi', 'email' => 'z@ornek.com', 'subject' => 'Merhaba',
            'message' => 'Bir sorum var', 'status' => 'yeni',
        ]);

        $metin = $this->sohbet()->karsila($this->admin());

        // Selam adla: "birçok projeyle çalışıyorum" diyen biri hangi panelde
        // olduğunu ilk cümleden anlamalı.
        $this->assertStringContainsString('Hakan', $metin);
        $this->assertStringContainsString('Yeni iletişim mesajı', $metin);
    }

    public function test_karsilama_bos_gunde_bos_pazari_hatirlatir(): void
    {
        $metin = $this->sohbet()->karsila($this->admin());

        $this->assertStringContainsString('bekleyen iş yok', $metin);
        // Envanter uyarısı karşılamada da durur: kendini kandırma koruması
        // yalnız raporda değil.
        $this->assertStringContainsString('aktif ilan yok', $metin);
    }

    public function test_karsilama_son_konusmayi_hatirlatir(): void
    {
        $admin = $this->admin();

        KahyaMesaji::create(['rol' => KahyaMesaji::ROL_SAHIP, 'metin' => 'Japonya ekle', 'user_id' => $admin->id]);

        $metin = $this->sohbet()->karsila($admin);

        $this->assertStringContainsString('Japonya ekle', $metin);
    }

    // ------------------------------------------------------------- Sohbet

    public function test_soru_eylem_tetiklemez(): void
    {
        $this->sahteAjan(['Şu an 0 aktif ilan var.']);

        $yanit = $this->sohbet()->sor('kaç ilan var?', $this->admin());

        $this->assertNull($yanit->eylem);
        $this->assertSame('Şu an 0 aktif ilan var.', $yanit->metin);
        $this->assertSame(0, KahyaEylemKaydi::query()->count());
        // İki mesaj da hafızaya yazıldı: soru + cevap.
        $this->assertSame(2, KahyaMesaji::query()->count());
    }

    public function test_japonya_ekle_ucta_uca(): void
    {
        $this->sahteAjan([
            new ToolCall('t1', 'ulke-ekle', ['kod' => 'JP', 'ad' => 'Japonya', 'emoji' => '🇯🇵']),
            'Japonya\'yı ekledim.',
        ]);

        $yanit = $this->sohbet()->sor('ülkeler kısmına Japonya ekle', $this->admin());

        $this->assertNotNull($yanit->eylem);
        $this->assertSame(KahyaEylemKaydi::DURUM_UYGULANDI, $yanit->eylem->durum);
        $this->assertFalse($yanit->onayBekliyor);
        $this->assertDatabaseHas('countries', ['code' => 'JP', 'name_tr' => 'Japonya']);
    }

    /**
     * F0'IN VAROLUŞ SEBEBİ: "üst kategori id'lerini bilmiyorum, sisteme
     * bakamıyorum" çağı kapandı. Model önce tablo-sorgula ile GERÇEK id'yi
     * bulur, sonra eylemi o id ile çağırır — iki araç, tek turda.
     */
    public function test_once_bakar_sonra_yapar(): void
    {
        $ust = Category::query()->whereNull('parent_id')->firstOrFail();

        $this->sahteAjan([
            new ToolCall('t1', 'tablo-sorgula', ['tablo' => 'categories', 'ara' => $ust->name]),
            new ToolCall('t2', 'kategori-ekle', ['ad' => 'Çatı Onarımı', 'ust_kategori' => $ust->id]),
            "\"{$ust->name}\" altına Çatı Onarımı'nı ekledim.",
        ]);

        $yanit = $this->sohbet()->sor("{$ust->name} altına Çatı Onarımı diye alt kategori aç", $this->admin());

        $this->assertNotNull($yanit->eylem);
        $this->assertSame(KahyaEylemKaydi::DURUM_UYGULANDI, $yanit->eylem->durum);
        $this->assertDatabaseHas('categories', ['name' => 'Çatı Onarımı', 'parent_id' => $ust->id]);
    }

    /**
     * Model katalogda olmayan bir araç adı uyduramaz: laravel/ai bilinmeyen
     * aracı sağlayıcıya hiç sunmaz, sahte gateway'de de karşılığı yoktur.
     * Bizim sınırımız ayrıca EylemCalistirici'de durur — bu test onu araç
     * katmanının ALTINDAN sınar: kataloğa sorulmadan hiçbir şey çalışmaz.
     */
    public function test_katalog_disi_eylem_calismaz(): void
    {
        $this->expectException(\RuntimeException::class);

        app(EylemCalistirici::class)->calistir('tum-ilanlari-sil', [], $this->admin());
    }

    public function test_yuksek_risk_sohbette_onay_bekler(): void
    {
        $this->sahteAjan([
            new ToolCall('t1', 'ulke-durum-degistir', ['kod' => 'DE', 'aktif' => false]),
            'Almanya\'yı kapatma işini onayına sundum.',
        ]);

        $yanit = $this->sohbet()->sor('Almanya\'yı kapat', $this->admin());

        $this->assertTrue($yanit->onayBekliyor);
        // Onay gelmeden hiçbir şey değişmedi.
        $this->assertDatabaseHas('countries', ['code' => 'DE', 'is_active' => true]);
    }

    /**
     * F0 KARARI (tasarım §2.2): iç yazma için onay kapısı kalktı — eskiden
     * yüksek riskli olan seo-doldur artık doğrudan uygulanır ve geri alınabilir.
     */
    public function test_seo_doldur_artik_onaysiz_uygulanir(): void
    {
        $this->sahteAjan([
            new ToolCall('t1', 'seo-doldur', [
                'baslik' => 'Nisoya — Yurtdışındaki Türklerin Pazaryeri',
                'aciklama' => 'Yurtdışındaki Türkler için ücretsiz ilan ve hizmet pazaryeri.',
            ]),
            'SEO metinlerini yazdım.',
        ]);

        $yanit = $this->sohbet()->sor('SEO\'yu doldur', $this->admin());

        $this->assertNotNull($yanit->eylem);
        $this->assertSame(KahyaEylemKaydi::DURUM_UYGULANDI, $yanit->eylem->durum);
        $this->assertFalse($yanit->onayBekliyor);
        $this->assertTrue($yanit->eylem->geriAlinabilirMi());
    }

    public function test_saglayici_cokerse_kibarca_soyler(): void
    {
        // Sahte ajan YOK: gerçek gateway koşar ama HTTP katmanı kapalı —
        // test ağı asla dışarı çıkmaz, hata yolu deterministiktir.
        Http::fake(['*' => Http::response(['error' => 'down'], 500)]);

        $yanit = $this->sohbet()->sor('kaç ilan var?', $this->admin());

        $this->assertNull($yanit->eylem);
        $this->assertStringContainsString('Yapay Zekâ Ayarları', $yanit->metin);
    }

    public function test_harcama_deftere_yazilir(): void
    {
        $this->sahteAjan(['Merhaba!']);

        $this->sohbet()->sor('selam', $this->admin());

        // Sahte gateway 0 token bildirir; satırın kendisi yeter — sayaç ilk
        // günden var (tasarım kararı), gerçek tokenlar canlıda dolar.
        $this->assertDatabaseHas('kahya_harcamalar', ['kaynak' => 'sohbet']);
    }

    // ------------------------------------------------------------ Yönerge

    private function ajan(?User $sahip = null): KahyaAjani
    {
        return new KahyaAjani(
            app(KahyaTeshisi::class),
            app(BekleyenIsler::class),
            app(PanelHaritasi::class),
            app(EylemKatalogu::class),
            app(EylemCalistirici::class),
            new EylemToplayici,
            collect(),
            $sahip,
        );
    }

    /** Yönerge site durumunu taşımalı; katalog metni ise artık araçlarda yaşar. */
    public function test_yonerge_durumu_araclar_katalogu_tasir(): void
    {
        $ajan = $this->ajan($this->admin());

        $yonerge = (string) $ajan->instructions();
        $this->assertStringContainsString('Aktif ilan: 0', $yonerge);

        // Katalog yönergeye gömülü DEĞİL — her eylem kendi aracı olarak sunulur.
        $adlar = array_map(
            fn (object $arac): string => method_exists($arac, 'name') ? $arac->name() : class_basename($arac),
            [...$ajan->tools()],
        );
        $this->assertContains('ulke-ekle', $adlar);
        $this->assertContains('kategori-ekle', $adlar);
        $this->assertContains('tablo-sorgula', $adlar);
        $this->assertContains('hatirla', $adlar);
        $this->assertContains('unut', $adlar);
        // 12 eylem (10 + F1'in hatirla/unut'u) + tablo-sorgula.
        $this->assertCount(13, $adlar);
    }

    /**
     * "X nerede?" cevabının kaynağı: yönerge panel haritasını ve site
     * kimliğini taşımalı — model panel ekranlarını ezberden bilemez.
     */
    public function test_yonerge_panel_haritasini_ve_site_kimligini_tasir(): void
    {
        $yonerge = (string) $this->ajan()->instructions();

        $this->assertStringContainsString('Panel haritası', $yonerge);
        $this->assertStringContainsString('/yonetim/tags', $yonerge);
        $this->assertStringContainsString('Site kimliği', $yonerge);
        $this->assertStringContainsString('Site adı: Nisoya', $yonerge);
    }

    // ------------------------------------------------------------- Arayüz

    public function test_moderator_sohbet_sayfasina_erisemez(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]));

        // Sohbetten EYLEM tetiklenir; moderatörün panelde yapamadığını
        // sohbetle yapabilmesi yetki yükseltmesi olurdu.
        $this->assertFalse(KahyaSohbet::canAccess());
    }

    public function test_admin_mesaj_gonderir_ve_gecmis_gorunur(): void
    {
        $this->sahteAjan(['Şu an bekleyen iş yok.']);

        Livewire::actingAs($this->admin())
            ->test(KahyaSohbet::class)
            ->set('mesaj', 'bekleyen iş var mı?')
            ->call('gonder')
            ->assertSuccessful()
            ->assertSee('bekleyen iş var mı?')
            ->assertSee('Şu an bekleyen iş yok.');
    }

    public function test_panelden_onaylanan_eylem_uygulanir(): void
    {
        $this->sahteAjan([
            new ToolCall('t1', 'ulke-durum-degistir', ['kod' => 'DE', 'aktif' => false]),
            'Onayına sundum.',
        ]);

        $admin = $this->admin();
        $this->sohbet()->sor('Almanya\'yı kapat', $admin);

        $kayit = KahyaEylemKaydi::query()->beklemede()->firstOrFail();

        Livewire::actingAs($admin)
            ->test(KahyaSohbet::class)
            ->call('eylemOnayla', $kayit->id)
            ->assertSuccessful();

        $this->assertDatabaseHas('countries', ['code' => 'DE', 'is_active' => false]);
    }

    public function test_panelden_geri_alma_calisir(): void
    {
        $this->sahteAjan([
            new ToolCall('t1', 'ulke-ekle', ['kod' => 'JP', 'ad' => 'Japonya']),
            'Ekledim.',
        ]);

        $admin = $this->admin();
        $this->sohbet()->sor('Japonya ekle', $admin);

        $kayit = KahyaEylemKaydi::query()->where('eylem', 'ulke-ekle')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(KahyaSohbet::class)
            ->call('eylemGeriAl', $kayit->id)
            ->assertSuccessful();

        $this->assertDatabaseMissing('countries', ['code' => 'JP']);
    }
}
