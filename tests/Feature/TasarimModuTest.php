<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\TasarimAyarlari;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TasarimModuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    public function test_default_mode_is_eski(): void
    {
        $this->assertSame('eski', setting('gorunum.tasarim_modu', 'eski'));
    }

    public function test_admin_can_open_tasarim_page(): void
    {
        $this->actingAs($this->admin())->get('/yonetim/tasarim-ayarlari')->assertOk();
    }

    public function test_member_cannot_open_tasarim_page(): void
    {
        $member = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($member)->get('/yonetim/tasarim-ayarlari')->assertForbidden();
    }

    public function test_admin_can_switch_to_yeni_mode(): void
    {
        Livewire::actingAs($this->admin())
            ->test(TasarimAyarlari::class)
            ->call('secModu', 'yeni')
            ->assertSet('aktifMod', 'yeni');

        $this->assertSame('yeni', setting('gorunum.tasarim_modu'));
        $this->assertDatabaseHas('site_settings', ['key' => 'gorunum.tasarim_modu', 'value' => 'yeni']);
    }

    public function test_admin_can_switch_back_to_eski_mode(): void
    {
        Settings::setMany(['gorunum.tasarim_modu' => 'yeni']);

        Livewire::actingAs($this->admin())
            ->test(TasarimAyarlari::class)
            ->call('secModu', 'eski')
            ->assertSet('aktifMod', 'eski');

        $this->assertSame('eski', setting('gorunum.tasarim_modu'));
    }

    public function test_secmodu_ignores_invalid_value(): void
    {
        Livewire::actingAs($this->admin())
            ->test(TasarimAyarlari::class)
            ->call('secModu', 'gecersiz')
            ->assertSet('aktifMod', 'eski');

        $this->assertSame('eski', setting('gorunum.tasarim_modu', 'eski'));
    }

    public function test_homepage_applies_yeni_tasarim_overrides(): void
    {
        Settings::setMany(['gorunum.tasarim_modu' => 'yeni']);

        $this->get('/')
            ->assertOk()
            ->assertSee('#0f5c42', false)
            ->assertSee('font-serif', false);
    }

    public function test_homepage_has_no_overrides_in_eski_mode(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('#0f5c42', false);
    }

    public function test_homepage_shows_pulse_map_only_in_yeni_mode(): void
    {
        // assertSee 2. parametresiz varsayılan olarak metni HTML-escape eder
        // (apostrof → &#039;) — bileşendeki ham apostrofla eşleşmesi için
        // escape=false gerekiyor.
        $this->get('/')->assertOk()->assertDontSee("Nisoya'nın Nabzı", false);

        Settings::setMany(['gorunum.tasarim_modu' => 'yeni']);

        $this->get('/')->assertOk()->assertSee("Nisoya'nın Nabzı", false);
    }
}
