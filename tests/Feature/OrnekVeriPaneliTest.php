<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\OrnekVeri;
use App\Models\DemoKaydi;
use App\Models\Listing;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Örnek Veri paneli — üretimin ve geri almanın insan kapısı.
 *
 * Sayfanın asıl işi üretmek değil, GERİ ALMAYI görünür tutmak: üretildiğini
 * unutmak, üretmekten daha tehlikeli.
 */
class OrnekVeriPaneliTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    public function test_admin_sayfayi_acabilir(): void
    {
        Livewire::actingAs($this->admin())
            ->test(OrnekVeri::class)
            ->assertSuccessful()
            ->assertSee('Bu veri gerçek değildir');
    }

    /** Örnek veri üretmek moderatörün işi değil. */
    public function test_moderator_erisemez(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]));

        $this->assertFalse(OrnekVeri::canAccess());
    }

    public function test_uye_erisemez(): void
    {
        $this->actingAs(User::factory()->create(['email_verified_at' => now()]));

        $this->assertFalse(OrnekVeri::canAccess());
    }

    public function test_panelden_uretilebilir(): void
    {
        Livewire::actingAs($this->admin())
            ->test(OrnekVeri::class)
            ->set('uyeSayisi', 2)
            ->set('ilanSayisi', 1)
            ->call('uret')
            ->assertSuccessful();

        $this->assertSame(2, User::query()->where('is_demo', true)->count());
        $this->assertSame(2, Listing::query()->where('is_demo', true)->count());
    }

    public function test_panelden_geri_alinabilir(): void
    {
        $sayfa = Livewire::actingAs($this->admin())
            ->test(OrnekVeri::class)
            ->set('uyeSayisi', 2)
            ->set('ilanSayisi', 1)
            ->call('uret');

        $sayfa->call('hepsiniSil')->assertSuccessful();

        $this->assertSame(0, User::query()->where('is_demo', true)->count());
        $this->assertSame(0, DemoKaydi::query()->count());
    }

    /**
     * Yapay zekânın örnek veri üretebilmesi bir İNSAN TIKLAMASIYLA açılır.
     * Bir kez açılır — her çağrıda onay istemek "ajana söyleyince yapsın"
     * isteğini boşa çıkarırdı.
     */
    public function test_mcp_kapisi_panelden_acilip_kapanir(): void
    {
        $this->assertFalse(app(OrnekVeri::class)->mcpAcikMi());

        $sayfa = Livewire::actingAs($this->admin())
            ->test(OrnekVeri::class)
            ->call('mcpKapisiniDegistir');

        $this->assertSame('1', Settings::get('demo.mcp_acik'));

        $sayfa->call('mcpKapisiniDegistir');

        $this->assertSame('0', Settings::get('demo.mcp_acik'));
    }
}
