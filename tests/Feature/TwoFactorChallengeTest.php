<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * 2FA login challenge akışı (Tranş B). Parola doğrulandıktan SONRA, tam giriş
 * ÖNCESİ 6 haneli TOTP kodu veya yedek kod ister.
 */
class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    private function twoFactorUser(string $secret, array $recovery = ['BACKUP01', 'BACKUP02']): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make('sifre1234'),
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => json_encode($recovery),
        ]);
    }

    public function test_login_without_2fa_logs_in_directly(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('sifre1234'),
            'email_verified_at' => now(),
        ]);

        $this->post('/giris', ['email' => $user->email, 'password' => 'sifre1234'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_2fa_redirects_to_challenge_and_does_not_authenticate(): void
    {
        $secret = app(Google2FA::class)->generateSecretKey();
        $user = $this->twoFactorUser($secret);

        $response = $this->post('/giris', ['email' => $user->email, 'password' => 'sifre1234']);

        $response->assertRedirect(route('two-factor.login'));
        // KRİTİK: kod girilene kadar oturum AÇILMAMALI.
        $this->assertGuest();
        $response->assertSessionHas('login.2fa.user_id', $user->id);
    }

    public function test_wrong_password_does_not_reach_challenge(): void
    {
        $secret = app(Google2FA::class)->generateSecretKey();
        $user = $this->twoFactorUser($secret);

        $this->post('/giris', ['email' => $user->email, 'password' => 'yanlis'])
            ->assertSessionHasErrors('email')
            ->assertSessionMissing('login.2fa.user_id');
        $this->assertGuest();
    }

    public function test_challenge_with_valid_totp_logs_in(): void
    {
        $google2fa = app(Google2FA::class);
        $secret = $google2fa->generateSecretKey();
        $user = $this->twoFactorUser($secret);

        // Önce parola adımı (bekleyen session'ı kurar).
        $this->post('/giris', ['email' => $user->email, 'password' => 'sifre1234']);
        $this->assertGuest();

        $response = $this->post('/iki-faktor-dogrula', ['code' => $google2fa->getCurrentOtp($secret)]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_challenge_with_invalid_totp_fails_and_stays_guest(): void
    {
        $secret = app(Google2FA::class)->generateSecretKey();
        $user = $this->twoFactorUser($secret);

        $this->withSession(['login.2fa.user_id' => $user->id])
            ->post('/iki-faktor-dogrula', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_challenge_with_recovery_code_logs_in_and_consumes_it(): void
    {
        $secret = app(Google2FA::class)->generateSecretKey();
        $user = $this->twoFactorUser($secret, ['BACKUP01', 'BACKUP02']);

        $this->withSession(['login.2fa.user_id' => $user->id])
            ->post('/iki-faktor-dogrula', ['recovery_code' => 'BACKUP01'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $remaining = json_decode($user->two_factor_recovery_codes, true);
        $this->assertNotContains('BACKUP01', $remaining); // tüketildi
        $this->assertContains('BACKUP02', $remaining);
    }

    public function test_used_recovery_code_cannot_be_reused(): void
    {
        $secret = app(Google2FA::class)->generateSecretKey();
        $user = $this->twoFactorUser($secret, ['BACKUP01']);

        // İlk kullanım başarılı.
        $this->withSession(['login.2fa.user_id' => $user->id])
            ->post('/iki-faktor-dogrula', ['recovery_code' => 'BACKUP01'])
            ->assertRedirect(route('dashboard'));

        // Oturumu kapat, aynı kodu tekrar dene → başarısız.
        $this->post('/cikis');
        $this->withSession(['login.2fa.user_id' => $user->id])
            ->post('/iki-faktor-dogrula', ['recovery_code' => 'BACKUP01'])
            ->assertSessionHasErrors('recovery_code');
        $this->assertGuest();
    }

    public function test_challenge_page_without_pending_session_redirects_to_login(): void
    {
        $this->get('/iki-faktor-dogrula')->assertRedirect(route('login'));
        $this->post('/iki-faktor-dogrula', ['code' => '123456'])->assertRedirect(route('login'));
    }

    public function test_two_factor_secret_is_encrypted_at_rest(): void
    {
        $secret = app(Google2FA::class)->generateSecretKey();
        $user = $this->twoFactorUser($secret);

        // Model üzerinden okununca çözülür (düz-metin secret).
        $this->assertSame($secret, $user->fresh()->two_factor_secret);

        // Ham DB değeri düz-metin OLMAMALI (şifreli saklanır).
        $raw = DB::table('users')->where('id', $user->id)->value('two_factor_secret');
        $this->assertNotSame($secret, $raw);
        $this->assertStringNotContainsString($secret, (string) $raw);
    }
}
