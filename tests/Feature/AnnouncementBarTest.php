<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\DuyuruBandi;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Faz 2 · G8 — site üstü duyuru bandı: panelden aç/kapa + metin + bağlantı.
 */
class AnnouncementBarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    public function test_bar_hidden_by_default(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Site duyurusu');
    }

    public function test_bar_shows_when_active(): void
    {
        Settings::setMany(['duyuru.aktif' => '1', 'duyuru.metin' => 'BAKIM DUYURUSU 123']);

        $this->get('/')->assertOk()->assertSee('BAKIM DUYURUSU 123');
    }

    public function test_bar_hidden_when_text_empty(): void
    {
        Settings::setMany(['duyuru.aktif' => '1', 'duyuru.metin' => '']);

        // Metin boşken band hiç render edilmez.
        $this->get('/')->assertOk()->assertDontSee('Site duyurusu');
    }

    public function test_bar_renders_link(): void
    {
        Settings::setMany([
            'duyuru.aktif' => '1',
            'duyuru.metin' => 'Yeni kampanya başladı',
            'duyuru.link' => 'https://ornek.com',
            'duyuru.link_metni' => 'Detaya git',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('https://ornek.com')
            ->assertSee('Detaya git');
    }

    public function test_admin_can_save_announcement(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);

        Livewire::actingAs($admin)
            ->test(DuyuruBandi::class)
            ->fillForm([
                'aktif' => true,
                'metin' => 'Panelden yazıldı',
                'link' => 'https://nisoya.com/kampanya',
                'link_metni' => 'Gör',
                'renk' => 'uyari',
                'kapatilabilir' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('site_settings', ['key' => 'duyuru.aktif', 'value' => '1']);
        $this->assertDatabaseHas('site_settings', ['key' => 'duyuru.metin', 'value' => 'Panelden yazıldı']);
        $this->assertDatabaseHas('site_settings', ['key' => 'duyuru.renk', 'value' => 'uyari']);
    }

    public function test_member_redirected_from_page(): void
    {
        $member = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($member)
            ->get('/yonetim/duyuru-bandi')
            ->assertRedirect(route('dashboard'));
    }
}
