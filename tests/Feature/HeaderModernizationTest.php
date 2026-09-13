<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\TasarimAyarlari;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\NavigationLinkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HeaderModernizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CountrySeeder::class, CurrencySeeder::class, NavigationLinkSeeder::class]);
    }

    public function test_guest_sees_modernized_header_with_flags_and_cta(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Nisoya');
        $response->assertSee('Acil');
        $response->assertSee('İlan Ver');
        $response->assertSee('⌘K');
        $response->assertSee('Keşfet');
        $response->assertSee('Giriş');
        $response->assertSee('Kayıt');
    }

    public function test_authenticated_user_sees_user_profile_dropdown_and_menu_items(): void
    {
        $user = User::factory()->create([
            'name' => 'Ahmet Yılmaz',
            'email' => 'ahmet@example.com',
            'role' => UserRole::Uye,
            'country_code' => 'KG',
            'city' => 'Bişkek',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Ahmet Yılmaz');
        $response->assertSee('Panelim');
        $response->assertSee('İlanlarım');
        $response->assertSee('Favorilerim');
        $response->assertSee('Profil & Ayarlar', false);
        $response->assertSee('Çıkış Yap');
        // Standard member should not see admin panel link in menu
        $response->assertDontSee('Yönetim Paneli (Admin)');
    }

    public function test_admin_user_sees_admin_panel_link_in_user_dropdown(): void
    {
        $admin = User::factory()->create([
            'name' => 'Sistem Yöneticisi',
            'email' => 'admin@example.com',
            'role' => UserRole::Admin,
        ]);

        $response = $this->actingAs($admin)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Sistem Yöneticisi');
        $response->assertSee('👑 Yönetici');
        $response->assertSee('Yönetim Paneli (Admin)');
    }

    public function test_admin_can_customize_header_cta_text(): void
    {
        Settings::set('gorunum.header_cta_metni', 'Ücretsiz İlan Ver');

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Ücretsiz İlan Ver');
    }

    public function test_country_selector_displays_flag_emojis_and_supports_styles(): void
    {
        $user = User::factory()->create([
            'country_code' => 'KG',
            'city' => 'Bişkek',
        ]);

        // Default: bayrak_isim
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
        $response->assertSee('🇰🇬');
        $response->assertSee('Kırgızistan');

        // Style: bayrak_yalniz
        Settings::set('gorunum.header_ulke_stili', 'bayrak_yalniz');
        $responseYalniz = $this->actingAs($user)->get('/');
        $responseYalniz->assertStatus(200);
        $responseYalniz->assertSee('🇰🇬');

        // Style: kod_isim
        Settings::set('gorunum.header_ulke_stili', 'kod_isim');
        $responseKod = $this->actingAs($user)->get('/');
        $responseKod->assertStatus(200);
        $responseKod->assertSee('KG');
    }

    public function test_tasarim_ayarlari_page_persists_header_controls(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(TasarimAyarlari::class)
            ->assertSet('headerAiRozet', true)
            ->assertSet('headerUlkeStili', 'bayrak_isim')
            ->assertSet('headerCtaMetni', 'İlan Ver')
            ->set('headerAiRozet', false)
            ->set('headerUlkeStili', 'bayrak_yalniz')
            ->set('headerCtaMetni', 'Hemen İlan Ver')
            ->call('kaydetCustom')
            ->assertHasNoErrors();

        $this->assertSame('0', Settings::get('gorunum.header_ai_rozet'));
        $this->assertSame('bayrak_yalniz', Settings::get('gorunum.header_ulke_stili'));
        $this->assertSame('Hemen İlan Ver', Settings::get('gorunum.header_cta_metni'));

        // Test reset to defaults
        Livewire::actingAs($admin)
            ->test(TasarimAyarlari::class)
            ->call('sifirla')
            ->assertHasNoErrors();

        $this->assertSame('1', Settings::get('gorunum.header_ai_rozet'));
        $this->assertSame('bayrak_isim', Settings::get('gorunum.header_ulke_stili'));
        $this->assertSame('İlan Ver', Settings::get('gorunum.header_cta_metni'));
    }
}
