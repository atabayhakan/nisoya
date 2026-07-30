<?php

namespace Tests\Feature;

use App\Ai\Kahya\KahyaAjani;
use App\Enums\UserRole;
use App\Filament\Pages\KahyaSohbet;
use App\Livewire\KahyaBalonu;
use App\Models\KahyaEylemKaydi;
use App\Models\User;
use App\Services\Kahya\Sohbet\KahyaSohbeti;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Ai;
use Laravel\Ai\Responses\Data\ToolCall;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Kâhya balonu — panelin her ekranındaki sohbet penceresi.
 *
 * Balon, sayfayla AYNI davranış trait'ini kullanır (KahyaSohbetiYurutur);
 * burada sınanan şey o ortaklığın iki ucu: balondan gönderilen mesaj aynı
 * boru hattından geçiyor mu, ve balon yalnız admin'e mi görünüyor.
 * (F0'dan beri boru hattı laravel/ai ajan döngüsü — sahte gateway ile sınanır.)
 */
class KahyaBalonuTest extends TestCase
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
        Ai::fakeAgent(KahyaAjani::class, ['Şu an bekleyen iş yok.']);

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
        Ai::fakeAgent(KahyaAjani::class, [
            new ToolCall('t1', 'ulke-durum-degistir', ['kod' => 'DE', 'aktif' => false]),
            'Onayına sundum.',
        ]);

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

    /**
     * Balon "X nerede?" penceresi: ajan yönergesi panel haritasını taşımalı.
     * (F0'da yönerge KahyaAjani::instructions()'a taşındı; balon da sayfa da
     * aynı ajanı kurar — harita oradan akar.)
     */
    public function test_balondan_sorulan_soru_panel_haritasini_gorur(): void
    {
        Ai::fakeAgent(KahyaAjani::class, ['Etiketler, Pazaryeri & Ticaret grubunda.']);

        Livewire::actingAs($this->admin())
            ->test(KahyaBalonu::class)
            ->call('ac')
            ->set('mesaj', 'etiketler nerede?')
            ->call('gonder')
            ->assertSuccessful();

        Ai::assertAgentWasPrompted(
            KahyaAjani::class,
            function (object $prompt): bool {
                $yonerge = (string) $prompt->agent->instructions();

                return str_contains($yonerge, 'Panel haritası')
                    && str_contains($yonerge, 'Etiketler');
            },
        );
    }
}
