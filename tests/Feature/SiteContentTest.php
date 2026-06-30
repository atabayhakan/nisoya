<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\IcerikAyarlari;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\SiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
    }

    public function test_setting_falls_back_to_config_default_when_not_in_db(): void
    {
        // DB boş — config varsayılanı dönmeli
        $this->assertSame('Hemen Başla', setting('home.cta_buton'));
        // Hiç tanımsız anahtar → verilen yedek
        $this->assertSame('YEDEK', setting('hic.yok', 'YEDEK'));
    }

    public function test_setting_prefers_db_value_over_default(): void
    {
        Settings::setMany(['home.cta_buton' => 'Hemen Katıl']);

        $this->assertSame('Hemen Katıl', setting('home.cta_buton'));
    }

    public function test_seeder_populates_all_default_keys(): void
    {
        $this->seed(SiteSettingSeeder::class);

        $this->assertDatabaseCount('site_settings', count(config('site_defaults.fields')));
        $this->assertDatabaseHas('site_settings', ['key' => 'home.hero_vurgu']);
    }

    public function test_admin_can_open_content_settings_page(): void
    {
        $this->seed(SiteSettingSeeder::class);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)->get('/yonetim/icerik-ayarlari')->assertOk();
    }

    public function test_member_cannot_open_content_settings_page(): void
    {
        $member = User::factory()->create([
            'role' => UserRole::Uye,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($member)->get('/yonetim/icerik-ayarlari')->assertForbidden();
    }

    public function test_saving_form_persists_settings_with_dotted_keys(): void
    {
        $this->seed(SiteSettingSeeder::class);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(IcerikAyarlari::class)
            ->set('data.home__cta_buton', 'Şimdi Başla')
            ->set('data.footer__aciklama', 'Yeni footer metni')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Şimdi Başla', setting('home.cta_buton'));
        $this->assertSame('Yeni footer metni', setting('footer.aciklama'));
        $this->assertDatabaseHas('site_settings', ['key' => 'home.cta_buton', 'value' => 'Şimdi Başla']);
    }

    public function test_home_page_reflects_content_settings(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class, SiteSettingSeeder::class]);

        Settings::setMany([
            'home.hero_vurgu' => 'ÖZELVURGU123',
            'home.cta_buton' => 'ŞİMDİKATIL',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('ÖZELVURGU123')
            ->assertSee('ŞİMDİKATIL');
    }
}
