<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\KahyaSohbet;
use App\Livewire\KahyaBalonu;
use App\Models\KahyaEylemKaydi;
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
 * Kâhya balonu — panelin her ekranındaki sohbet penceresi.
 *
 * Balon, sayfayla AYNI davranış trait'ini kullanır (KahyaSohbetiYurutur);
 * burada sınanan şey o ortaklığın iki ucu: balondan gönderilen mesaj aynı
 * boru hattından geçiyor mu, ve balon yalnız admin'e mi görünüyor.
 */
class KahyaBalonuTest extends TestCase
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
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    // ------------------------------------------------------------- Görünürlük

    public function test_balon_panel_sayfalarinda_admin_icin_gorunur(): void
    {
        $this->actingAs($this->admin())
            ->get('/yonetim')
            ->assertOk()
            ->assertSee('kahya-balonu', false);
    }

    /**
     * "Kâhya ile Konuş" sayfasında balon GİZLİ: aynı sohbetin iki kopyası
     * aynı ekranda kafa karıştırır — hangisine yazdıysan diğeri güncel değil
     * sanılır (oysa ikisi de aynı defteri okur).
     */
    public function test_balon_sohbet_sayfasinda_gizli(): void
    {
        $this->actingAs($this->admin())
            ->get(KahyaSohbet::getUrl())
            ->assertOk()
            ->assertDontSee('kahya-balonu', false);
    }

    /**
     * Balondan EYLEM tetiklenebilir; sayfayla aynı gerekçeyle yalnız admin.
     * Render hook zaten admin dışına balon basmaz — bu test, bileşeni
     * DOĞRUDAN mount etmeye çalışan isteklere karşı ikinci kemeri sınar.
     */
    public function test_uye_bileseni_dogrudan_mount_edemez(): void
    {
        Livewire::actingAs(User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]))
            ->test(KahyaBalonu::class)
            ->assertForbidden();
    }

    // ------------------------------------------------------------- Davranış

    public function test_balondan_mesaj_gonderilir(): void
    {
        $this->sahte->yanit = ['cevap' => 'Şu an bekleyen iş yok.', 'eylem' => ''];

        Livewire::actingAs($this->admin())
            ->test(KahyaBalonu::class)
            ->call('ac')
            ->set('mesaj', 'bekleyen iş var mı?')
            ->call('gonder')
            ->assertSuccessful()
            ->assertSee('bekleyen iş var mı?')
            ->assertSee('Şu an bekleyen iş yok.');
    }

    /** Balondan onaylanan eylem, sayfadan onaylanmış gibi uygulanmalı. */
    public function test_balondan_onay_eylemi_uygular(): void
    {
        $this->sahte->yanit = [
            'cevap' => 'Onayına sunuyorum.',
            'eylem' => 'ulke-durum-degistir',
            'parametreler' => ['kod' => 'DE', 'aktif' => false],
        ];

        $admin = $this->admin();
        app(KahyaSohbeti::class)->sor('Almanya\'yı kapat', $admin);

        $kayit = KahyaEylemKaydi::query()->beklemede()->firstOrFail();

        Livewire::actingAs($admin)
            ->test(KahyaBalonu::class)
            ->call('ac')
            ->call('eylemOnayla', $kayit->id)
            ->assertSuccessful();

        $this->assertDatabaseHas('countries', ['code' => 'DE', 'is_active' => false]);
    }

    /** Balon "X nerede?" penceresi: yönergeye panel haritası gitmeli. */
    public function test_balondan_sorulan_soru_panel_haritasini_gorur(): void
    {
        $this->sahte->yanit = ['cevap' => 'Etiketler, Pazaryeri & Ticaret grubunda.', 'eylem' => ''];

        Livewire::actingAs($this->admin())
            ->test(KahyaBalonu::class)
            ->call('ac')
            ->set('mesaj', 'etiketler nerede?')
            ->call('gonder')
            ->assertSuccessful();

        $this->assertStringContainsString('Panel haritası', (string) $this->sahte->sonYonerge);
        $this->assertStringContainsString('Etiketler', (string) $this->sahte->sonYonerge);
    }
}
