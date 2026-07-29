<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\KahyaSohbet;
use App\Models\ContactMessage;
use App\Models\KahyaEylemKaydi;
use App\Models\KahyaMesaji;
use App\Models\User;
use App\Services\Ai\AiManager;
use App\Services\Kahya\Sohbet\KahyaSohbeti;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\SahteAiSaglayici;
use Tests\Support\SahteAiYonetici;
use Tests\TestCase;

/**
 * Kâhya sohbeti — sahibin Türkçe cümlesi ile eylem arasındaki boru hattı.
 *
 * Testler modelin ZEKÂSINI sınamaz (model sahte, kararı test yazar); kararın
 * ETRAFINI sınar: katalog sınırı çalışıyor mu, "modelin cümlesi ≠ olan biten"
 * ayrımı duruyor mu, hafıza yazılıyor mu, arayüz doğru eylemi onaylıyor mu.
 */
class KahyaSohbetTest extends TestCase
{
    use RefreshDatabase;

    private SahteAiSaglayici $sahte;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);

        $this->sahte = new SahteAiSaglayici;
        $this->app->instance(AiManager::class, new SahteAiYonetici($this->sahte));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'name' => 'Hakan Atabay', 'email_verified_at' => now()]);
    }

    private function sohbet(): KahyaSohbeti
    {
        return app(KahyaSohbeti::class);
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
        $this->sahte->yanit = ['cevap' => 'Şu an 0 aktif ilan var.', 'eylem' => ''];

        $yanit = $this->sohbet()->sor('kaç ilan var?', $this->admin());

        $this->assertNull($yanit->eylem);
        $this->assertSame(0, KahyaEylemKaydi::query()->count());
        // İki mesaj da hafızaya yazıldı: soru + cevap.
        $this->assertSame(2, KahyaMesaji::query()->count());
    }

    public function test_japonya_ekle_ucta_uca(): void
    {
        $this->sahte->yanit = [
            'cevap' => 'Japonya\'yı ekliyorum.',
            'eylem' => 'ulke-ekle',
            'parametreler' => ['kod' => 'JP', 'ad' => 'Japonya', 'emoji' => '🇯🇵'],
        ];

        $yanit = $this->sohbet()->sor('ülkeler kısmına Japonya ekle', $this->admin());

        $this->assertNotNull($yanit->eylem);
        $this->assertSame(KahyaEylemKaydi::DURUM_UYGULANDI, $yanit->eylem->durum);
        $this->assertFalse($yanit->onayBekliyor);
        $this->assertDatabaseHas('countries', ['code' => 'JP', 'name_tr' => 'Japonya']);
        // Yanıt GERÇEK sonucu taşır, modelin iddiasını değil.
        $this->assertStringContainsString('✅', $yanit->metin);
    }

    /**
     * Model katalogda olmayan bir ad uydurursa kullanıcı DÜRÜSTÇE bilgilendirilir
     * ve hiçbir şey çalışmaz — modelin "yaptım" demesi bir şey değiştirmez.
     */
    public function test_katalog_disi_eylem_durustce_reddedilir(): void
    {
        $this->sahte->yanit = [
            'cevap' => 'Siliyorum.',
            'eylem' => 'tum-ilanlari-sil',
            'parametreler' => [],
        ];

        $yanit = $this->sohbet()->sor('bütün ilanları sil', $this->admin());

        $this->assertNull($yanit->eylem);
        $this->assertStringContainsString('yapamıyorum', $yanit->metin);
        $this->assertSame(0, KahyaEylemKaydi::query()->count());
    }

    public function test_yuksek_risk_sohbette_onay_bekler(): void
    {
        $this->sahte->yanit = [
            'cevap' => 'Almanya\'yı pasife çekmek istiyorsun.',
            'eylem' => 'ulke-durum-degistir',
            'parametreler' => ['kod' => 'DE', 'aktif' => false],
        ];

        $yanit = $this->sohbet()->sor('Almanya\'yı kapat', $this->admin());

        $this->assertTrue($yanit->onayBekliyor);
        $this->assertStringContainsString('onayına sunuyorum', $yanit->metin);
        // Onay gelmeden hiçbir şey değişmedi.
        $this->assertDatabaseHas('countries', ['code' => 'DE', 'is_active' => true]);
    }

    public function test_saglayici_cokerse_kibarca_soyler(): void
    {
        $this->sahte->yanit = null;
        $this->sahte->sonHata = 'bağlantı zaman aşımı';

        $yanit = $this->sohbet()->sor('kaç ilan var?', $this->admin());

        $this->assertNull($yanit->eylem);
        $this->assertStringContainsString('Yapay Zekâ Ayarları', $yanit->metin);
    }

    /** Yönerge katalog metnini ve site durumunu taşımalı — model kör karar veremez. */
    public function test_yonerge_katalogu_ve_durumu_tasir(): void
    {
        $this->sahte->yanit = ['cevap' => 'Tamam.', 'eylem' => ''];

        $this->sohbet()->sor('merhaba', $this->admin());

        $this->assertNotNull($this->sahte->sonYonerge);
        $this->assertStringContainsString('### ulke-ekle', $this->sahte->sonYonerge);
        $this->assertStringContainsString('Aktif ilan: 0', $this->sahte->sonYonerge);
    }

    /**
     * BU TEST BİR CANLI HATANIN MEZAR TAŞI.
     *
     * `response_format: json_object` kullanan OpenAI-uyumlu uçlar (OpenAI,
     * OpenRouter üzerinden geçen OpenAI-uyumlu modeller dâhil) mesaj
     * içeriğinde "json" kelimesi GEÇMİYORSA 400 hatası döner — bu, sahte
     * sağlayıcının maskeleyemeyeceği bir HTTP kısıtı, model karar mantığı
     * değil. Yönerge tamamen Türkçe yazıldığı için ilk sürümde hiçbir yerde
     * "json" geçmiyordu; canlıda ilk gerçek çağrı bu yüzden düştü
     * (doğrulandı: 2026-07-29, üretimde `HTTP 400: Provider returned error`).
     *
     * Sahte sağlayıcı bu HTTP katmanını atladığı için diğer testler bunu
     * YAKALAYAMAZ — bu yüzden yönergenin kelimesi ayrıca sınanıyor.
     */
    public function test_yonerge_json_kelimesini_icerir(): void
    {
        $this->sahte->yanit = ['cevap' => 'Tamam.', 'eylem' => ''];

        $this->sohbet()->sor('merhaba', $this->admin());

        $this->assertStringContainsStringIgnoringCase('json', (string) $this->sahte->sonYonerge);
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
        $this->sahte->yanit = ['cevap' => 'Şu an bekleyen iş yok.', 'eylem' => ''];

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
        $this->sahte->yanit = [
            'cevap' => 'Onayına sunuyorum.',
            'eylem' => 'ulke-durum-degistir',
            'parametreler' => ['kod' => 'DE', 'aktif' => false],
        ];

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
        $this->sahte->yanit = [
            'cevap' => 'Ekliyorum.',
            'eylem' => 'ulke-ekle',
            'parametreler' => ['kod' => 'JP', 'ad' => 'Japonya'],
        ];

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
