<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\SeoAyarlari;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Faz 3 · G7 — SEO defaultları (başlık/açıklama/OG görsel/görünürlük) panelden.
 */
class SeoSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    public function test_default_title_and_description_present(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Nisoya — Ne İş Olursa Yaparım')
            ->assertSee('güvenle hizmet al');
    }

    public function test_override_title(): void
    {
        Settings::setMany(['seo.default_title' => 'Özel Site Başlığı 42']);

        $this->get('/')->assertOk()->assertSee('Özel Site Başlığı 42');
    }

    public function test_override_description(): void
    {
        Settings::setMany(['seo.default_description' => 'Bambaşka bir açıklama metni.']);

        $this->get('/')->assertOk()->assertSee('Bambaşka bir açıklama metni.');
    }

    public function test_indexable_by_default(): void
    {
        $this->get('/')->assertOk()->assertDontSee('noindex');
    }

    public function test_noindex_when_hidden(): void
    {
        Settings::setMany(['seo.robots_index' => '0']);

        $this->get('/')->assertOk()->assertSee('noindex');
    }

    public function test_og_image_uses_uploaded_path(): void
    {
        Settings::setMany(['seo.og_image' => 'seo/paylasim.png']);

        $this->get('/')->assertOk()->assertSee('/storage/seo/paylasim.png');
    }

    public function test_admin_can_save_seo_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);

        Livewire::actingAs($admin)
            ->test(SeoAyarlari::class)
            ->fillForm([
                'default_title' => 'Kaydedilen Başlık',
                'default_description' => 'Kaydedilen açıklama',
                'robots_index' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('site_settings', ['key' => 'seo.default_title', 'value' => 'Kaydedilen Başlık']);
        $this->assertDatabaseHas('site_settings', ['key' => 'seo.robots_index', 'value' => '0']);
    }

    public function test_member_redirected_from_page(): void
    {
        $member = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($member)
            ->get('/yonetim/seo-ayarlari')
            ->assertRedirect(route('dashboard'));
    }
}
