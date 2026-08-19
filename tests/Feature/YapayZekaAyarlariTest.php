<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\YapayZekaAyarlari;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class YapayZekaAyarlariTest extends TestCase
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

    public function test_admin_can_open_page(): void
    {
        $this->actingAs($this->admin())->get('/yonetim/yapay-zeka-ayarlari')->assertOk();
    }

    public function test_member_cannot_open_page(): void
    {
        $member = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($member)->get('/yonetim/yapay-zeka-ayarlari')->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_save_ai_settings(): void
    {
        Livewire::actingAs($this->admin())
            ->test(YapayZekaAyarlari::class)
            ->fillForm([
                'saglayici' => 'openrouter',
                'api_anahtari' => 'sk-or-test-key',
                'model' => 'openai/gpt-4o-mini',
                'hizli_ilan_aktif' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('site_settings', ['key' => 'ai.saglayici', 'value' => 'openrouter']);
        // API anahtarı artık DB'de ŞİFRELİ durur (bkz. Settings::SIRLI_ANAHTARLAR):
        // ham satırı kontrol etmek yanlış katman olurdu. Anlamlı iddia, yazma ve
        // okumanın aynı değeri vermesi — ve ham değerin düz metin OLMAMASI.
        $this->assertSame('sk-or-test-key', Settings::get('ai.api_anahtari'));
        $this->assertDatabaseMissing('site_settings', ['key' => 'ai.api_anahtari', 'value' => 'sk-or-test-key']);
        $this->assertDatabaseHas('site_settings', ['key' => 'ai.model', 'value' => 'openai/gpt-4o-mini']);
        $this->assertDatabaseHas('site_settings', ['key' => 'ai.hizli_ilan_aktif', 'value' => '1']);
    }

    public function test_admin_can_disable_quick_listing(): void
    {
        Livewire::actingAs($this->admin())
            ->test(YapayZekaAyarlari::class)
            ->fillForm([
                'saglayici' => 'openrouter',
                'api_anahtari' => 'sk-or-test-key',
                'model' => '',
                'hizli_ilan_aktif' => false,
            ])
            ->call('save');

        $this->assertDatabaseHas('site_settings', ['key' => 'ai.hizli_ilan_aktif', 'value' => '0']);
    }

    /** En görünür AI yüzeyi — deploy beklemeden kapatılabilmeli (bkz. NisoyaAiYonlendirici). */
    public function test_admin_can_disable_nisoya_ai_arama(): void
    {
        Livewire::actingAs($this->admin())
            ->test(YapayZekaAyarlari::class)
            ->fillForm([
                'saglayici' => 'openrouter',
                'api_anahtari' => 'sk-or-test-key',
                'model' => '',
                'nisoya_ai_arama_aktif' => false,
            ])
            ->call('save');

        $this->assertDatabaseHas('site_settings', ['key' => 'ai.nisoya_ai_arama_aktif', 'value' => '0']);
    }

    public function test_test_button_reports_success_on_valid_response(): void
    {
        Http::fake(['openrouter.ai/*' => Http::response([
            'choices' => [['finish_reason' => 'stop', 'message' => ['content' => '{"ok":true}']]],
        ])]);

        Livewire::actingAs($this->admin())
            ->test(YapayZekaAyarlari::class)
            ->fillForm([
                'saglayici' => 'openrouter',
                'api_anahtari' => 'sk-or-test-key',
                'model' => 'openai/gpt-4o-mini',
                'hizli_ilan_aktif' => true,
            ])
            ->call('testEt')
            ->assertNotified();

        Http::assertSent(fn ($r) => str_contains($r->url(), 'openrouter.ai'));
    }

    public function test_test_button_warns_when_key_missing(): void
    {
        Http::fake();

        Livewire::actingAs($this->admin())
            ->test(YapayZekaAyarlari::class)
            ->fillForm([
                'saglayici' => 'openrouter',
                'api_anahtari' => '',
                'model' => '',
                'hizli_ilan_aktif' => true,
            ])
            ->call('testEt')
            ->assertNotified();

        Http::assertNothingSent();
    }

    /** DB ayarları config('ai.*') üzerine yazılmalı (mergeAiConfig — boot-time). */
    public function test_db_settings_override_ai_config(): void
    {
        Settings::setMany([
            'ai.saglayici' => 'openrouter',
            'ai.api_anahtari' => 'sk-or-db-key',
            'ai.model' => 'google/gemini-2.0-flash-001',
            'ai.hizli_ilan_aktif' => '0',
            'ai.nisoya_ai_arama_aktif' => '0',
        ]);

        // boot-time merge'i doğrudan çağır (protected → reflection).
        $provider = new AppServiceProvider($this->app);
        $method = new \ReflectionMethod($provider, 'mergeAiConfig');
        $method->setAccessible(true);
        $method->invoke($provider);

        $this->assertSame('openrouter', config('ai.default'));
        $this->assertSame('sk-or-db-key', config('ai.providers.openrouter.api_key'));
        $this->assertSame('google/gemini-2.0-flash-001', config('ai.providers.openrouter.model'));
        $this->assertFalse(config('ai.features.quick_listing'));
        $this->assertFalse(config('ai.features.nisoya_ai_arama'));
    }

    public function test_empty_db_settings_leave_env_defaults(): void
    {
        // Hiç ayar yokken merge config'i bozmamalı (env/kod varsayılanı korunur).
        config(['ai.default' => 'anthropic', 'ai.providers.anthropic.api_key' => 'env-key']);

        $provider = new AppServiceProvider($this->app);
        $method = new \ReflectionMethod($provider, 'mergeAiConfig');
        $method->setAccessible(true);
        $method->invoke($provider);

        $this->assertSame('anthropic', config('ai.default'));
        $this->assertSame('env-key', config('ai.providers.anthropic.api_key'));
    }

    /** Ana anahtar '0' ise bireysel özellikler açık olsa bile TÜM AI kapanmalı. */
    public function test_master_switch_off_disables_all_ai_features(): void
    {
        Settings::setMany([
            'ai.hizli_ilan_aktif' => '1',
            'ai.moderasyon_aktif' => '1',
            'ai.nisoya_ai_arama_aktif' => '1',
            'ai.aktif' => '0',
        ]);

        $provider = new AppServiceProvider($this->app);
        $method = new \ReflectionMethod($provider, 'mergeAiConfig');
        $method->setAccessible(true);
        $method->invoke($provider);

        $this->assertFalse(config('ai.features.quick_listing'));
        $this->assertFalse(config('ai.features.image_moderation'));
        $this->assertFalse(config('ai.features.nisoya_ai_arama'));
    }

    public function test_admin_can_turn_off_master_switch(): void
    {
        Livewire::actingAs($this->admin())
            ->test(YapayZekaAyarlari::class)
            ->fillForm([
                'yapay_zeka_aktif' => false,
                'saglayici' => 'openrouter',
                'api_anahtari' => 'sk-or-test',
                'model' => '',
                'hizli_ilan_aktif' => true,
                'moderasyon_aktif' => true,
            ])
            ->call('save');

        $this->assertDatabaseHas('site_settings', ['key' => 'ai.aktif', 'value' => '0']);
    }
}
