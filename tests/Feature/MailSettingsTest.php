<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\MailAyarlari;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Faz 1 · G3 — Mail (SMTP) ayarları: panelden yönetim, DB > env runtime override
 * ve test e-postası. Sahip e-posta sağlayıcısını SSH/.env'e dokunmadan değiştirir.
 */
class MailSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
    }

    // ---------------------------------------------------------- Erişim

    public function test_admin_can_open_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/yonetim/mail-ayarlari')
            ->assertOk()
            ->assertSee('SMTP');
    }

    public function test_member_is_redirected(): void
    {
        $member = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($member)
            ->get('/yonetim/mail-ayarlari')
            ->assertRedirect(route('dashboard'));
    }

    public function test_moderator_is_forbidden(): void
    {
        $mod = User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]);

        $this->actingAs($mod)
            ->get('/yonetim/mail-ayarlari')
            ->assertForbidden();
    }

    // ---------------------------------------------------------- Kaydetme

    public function test_admin_can_save_mail_settings(): void
    {
        Livewire::actingAs($this->admin())
            ->test(MailAyarlari::class)
            ->fillForm([
                'host' => 'smtp.hostinger.com',
                'port' => '465',
                'username' => 'info@nisoya.com',
                'password' => 'gizli-parola',
                'encryption' => 'ssl',
                'from_address' => 'info@nisoya.com',
                'from_name' => 'Nisoya',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('site_settings', ['key' => 'mail.host', 'value' => 'smtp.hostinger.com']);
        $this->assertDatabaseHas('site_settings', ['key' => 'mail.port', 'value' => '465']);
        // Parola artık DB'de ŞİFRELİ durur (bkz. Settings::SIRLI_ANAHTARLAR):
        // ham satırı kontrol etmek yanlış katman olurdu. Anlamlı iddia, yazma
        // ve okumanın aynı değeri vermesi — ve ham değerin düz metin OLMAMASI.
        $this->assertSame('gizli-parola', Settings::get('mail.password'));
        $this->assertDatabaseMissing('site_settings', ['key' => 'mail.password', 'value' => 'gizli-parola']);
        $this->assertDatabaseHas('site_settings', ['key' => 'mail.encryption', 'value' => 'ssl']);
    }

    public function test_blank_password_keeps_existing(): void
    {
        Settings::setMany(['mail.password' => 'eski-parola', 'mail.host' => 'smtp.eski.com']);

        Livewire::actingAs($this->admin())
            ->test(MailAyarlari::class)
            ->fillForm([
                'host' => 'smtp.yeni.com',
                'port' => '587',
                'username' => 'u@nisoya.com',
                'password' => '', // boş → mevcut korunmalı
                'encryption' => 'tls',
                'from_address' => 'u@nisoya.com',
                'from_name' => 'Nisoya',
            ])
            ->call('save');

        $this->assertSame('eski-parola', Settings::get('mail.password'));
        $this->assertDatabaseHas('site_settings', ['key' => 'mail.host', 'value' => 'smtp.yeni.com']);
    }

    // ---------------------------------------------------- Runtime merge

    public function test_db_settings_override_mail_config_ssl(): void
    {
        Settings::setMany([
            'mail.host' => 'smtp.example.com',
            'mail.port' => '465',
            'mail.username' => 'u@example.com',
            'mail.password' => 'secret',
            'mail.encryption' => 'ssl',
            'mail.from_address' => 'no-reply@nisoya.com',
            'mail.from_name' => 'Nisoya',
        ]);

        $this->invokeMergeMailConfig();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame('u@example.com', config('mail.mailers.smtp.username'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme')); // SSL → smtps
        $this->assertSame('no-reply@nisoya.com', config('mail.from.address'));
        $this->assertSame('Nisoya', config('mail.from.name'));
    }

    public function test_tls_maps_to_smtp_scheme(): void
    {
        Settings::setMany(['mail.host' => 'smtp.example.com', 'mail.port' => '587', 'mail.encryption' => 'tls']);

        $this->invokeMergeMailConfig();

        $this->assertSame('smtp', config('mail.mailers.smtp.scheme')); // TLS → smtp (STARTTLS)
    }

    public function test_empty_db_settings_leave_env_defaults(): void
    {
        config([
            'mail.default' => 'log',
            'mail.mailers.smtp.host' => 'env-host',
        ]);

        // Hiç mail ayarı yokken merge config'i bozmamalı.
        $this->invokeMergeMailConfig();

        $this->assertSame('log', config('mail.default'));
        $this->assertSame('env-host', config('mail.mailers.smtp.host'));
    }

    // ---------------------------------------------------- Test e-postası

    public function test_test_email_sends_with_valid_settings(): void
    {
        Mail::fake();

        Livewire::actingAs($this->admin())
            ->test(MailAyarlari::class)
            ->fillForm([
                'host' => 'smtp.example.com',
                'port' => '465',
                'username' => 'u@example.com',
                'password' => 'secret',
                'encryption' => 'ssl',
                'from_address' => 'no-reply@nisoya.com',
                'from_name' => 'Nisoya',
                'test_alici' => 'hedef@test.com',
            ])
            ->call('testEt')
            ->assertNotified();
    }

    public function test_test_email_warns_without_host(): void
    {
        Mail::fake();

        Livewire::actingAs($this->admin())
            ->test(MailAyarlari::class)
            ->fillForm([
                'host' => '',
                'port' => '',
                'encryption' => 'ssl',
                'test_alici' => 'hedef@test.com',
            ])
            ->call('testEt')
            ->assertNotified();

        Mail::assertNothingSent();
    }

    private function invokeMergeMailConfig(): void
    {
        $provider = new AppServiceProvider($this->app);
        $method = new \ReflectionMethod($provider, 'mergeMailConfig');
        $method->setAccessible(true);
        $method->invoke($provider);
    }
}
