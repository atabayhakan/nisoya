<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_2fa_settings_page(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/panel/profil/iki-faktor');
        $response->assertOk();
        $response->assertSee('İki Faktörlü Kimlik Doğrulama');
    }

    public function test_user_can_initiate_2fa_setup(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->post('/panel/profil/iki-faktor/kur');
        $response->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at); // henüz onaylanmadı
    }

    public function test_user_cannot_setup_2fa_twice(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'two_factor_secret' => 'EXISTING',
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/panel/profil/iki-faktor/kur');
        $response->assertSessionHasErrors('two_factor');
    }

    public function test_user_can_confirm_2fa_with_valid_code(): void
    {
        // Gerçek Google2FA kullan — secret'i generate edip kendi ürettiği
        // koduyla doğrula. Zaman penceresi sorunlarına karşı aynı saniyede üret.
        $google2fa = app(Google2FA::class);
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'two_factor_secret' => $secret,
        ]);

        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->actingAs($user)->post('/panel/profil/iki-faktor/onayla', [
            'code' => $validCode,
        ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertNotEmpty($user->two_factor_recovery_codes);
    }

    public function test_user_cannot_confirm_2fa_with_invalid_code(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'two_factor_secret' => app(Google2FA::class)->generateSecretKey(),
        ]);

        $response = $this->actingAs($user)->post('/panel/profil/iki-faktor/onayla', [
            'code' => '000000',
        ]);

        $response->assertSessionHasErrors('code');
        $user->refresh();
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_user_cannot_confirm_2fa_without_secret(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'two_factor_secret' => null,
        ]);

        $response = $this->actingAs($user)->post('/panel/profil/iki-faktor/onayla', [
            'code' => '123456',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_user_can_disable_2fa_with_valid_code_and_password(): void
    {
        $google2fa = $this->createMock(Google2FA::class);
        $google2fa->method('verifyKey')->willReturn(true);
        $this->app->instance(Google2FA::class, $google2fa);

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => json_encode(['CODE1', 'CODE2']),
        ]);

        $response = $this->actingAs($user)->post('/panel/profil/iki-faktor/kapat', [
            'current_password' => 'password',
            'code' => '123456',
        ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_user_cannot_disable_2fa_with_wrong_password(): void
    {
        $google2fa = $this->createMock(Google2FA::class);
        $google2fa->method('verifyKey')->willReturn(true);
        $this->app->instance(Google2FA::class, $google2fa);

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => \Illuminate\Support\Facades\Hash::make('correct'),
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/panel/profil/iki-faktor/kapat', [
            'current_password' => 'wrong',
            'code' => '123456',
        ]);

        $response->assertSessionHasErrors('current_password');
        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
    }

    public function test_recovery_codes_are_generated_and_can_be_consumed(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'two_factor_secret' => app(Google2FA::class)->generateSecretKey(),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => json_encode(['AAAA-BBBB', 'CCCC-DDDD']),
        ]);

        $controller = app(\App\Http\Controllers\TwoFactorController::class);

        // İlk kod doğru
        $this->assertTrue($controller->useRecoveryCode(request(), $user, 'AAAA-BBBB'));

        $user->refresh();
        $codes = json_decode($user->two_factor_recovery_codes, true);
        $this->assertCount(1, $codes);
        $this->assertNotContains('AAAA-BBBB', $codes);
        $this->assertContains('CCCC-DDDD', $codes);

        // Aynı kod tekrar kullanılamaz
        $this->assertFalse($controller->useRecoveryCode(request(), $user, 'AAAA-BBBB'));
    }

    public function test_invalid_recovery_code_returns_false(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'two_factor_secret' => app(Google2FA::class)->generateSecretKey(),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => json_encode(['VALID-CODE']),
        ]);

        $controller = app(\App\Http\Controllers\TwoFactorController::class);
        $this->assertFalse($controller->useRecoveryCode(request(), $user, 'INVALID-CODE'));
    }

    public function test_profile_edit_page_links_to_2fa(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/panel/profil');
        $response->assertOk();
        $response->assertSee('İki Faktörlü Doğrulama', false);
    }
}