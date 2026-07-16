<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_and_login_screens_render(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);

        $this->get('/kayit')->assertOk()->assertSee('Kayıt Ol');
        $this->get('/giris')->assertOk()->assertSee('Giriş Yap');
        $this->get('/sifremi-unuttum')->assertOk();
    }

    public function test_new_user_can_register(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);

        $response = $this->post('/kayit', [
            'name' => 'Ayşe Yılmaz',
            'email' => 'ayse@example.com',
            'password' => 'sifre1234',
            'password_confirmation' => 'sifre1234',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'preferred_currency' => 'EUR',
            'terms' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'ayse@example.com',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'preferred_currency' => 'EUR',
            'role' => 'uye',
        ]);
        $this->assertNotNull(User::where('email', 'ayse@example.com')->first()->username);
    }

    public function test_registration_requires_terms_and_valid_country(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);

        $this->post('/kayit', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'sifre1234',
            'password_confirmation' => 'sifre1234',
            'country_code' => 'XX', // geçersiz
            'preferred_currency' => 'EUR',
            // terms yok
        ])->assertSessionHasErrors(['country_code', 'terms']);

        $this->assertGuest();
    }

    public function test_user_can_login_with_correct_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('sifre1234'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/giris', [
            'email' => $user->email,
            'password' => 'sifre1234',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('sifre1234'),
        ]);

        $this->post('/giris', [
            'email' => $user->email,
            'password' => 'yanlis-sifre',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cikis');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_unverified_user_is_redirected_from_panel(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($user)->get('/panel')->assertRedirect(route('verification.notice'));
    }

    public function test_verified_user_can_access_panel(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/panel')->assertOk()->assertSee('Merhaba');
    }

    /**
     * Masaüstü header'daki Çıkış formu 'hidden md:block' ile mobilde gizli —
     * mobil kullanıcının çıkış yapabileceği tek yer bu sayfa (2026-07-17
     * kullanıcı raporu: mobilde hiçbir yerde Çıkış Yap butonu yoktu).
     */
    public function test_dashboard_has_visible_logout_button(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/panel')
            ->assertOk()
            ->assertSee('Çıkış Yap')
            ->assertSee(route('logout'), false);
    }

    public function test_guest_cannot_access_panel(): void
    {
        $this->get('/panel')->assertRedirect(route('login'));
    }

    public function test_email_can_be_verified_via_signed_link(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)->get($url);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_registration_sends_verification_email(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
        Notification::fake();

        $this->post('/kayit', [
            'name' => 'Mehmet',
            'email' => 'mehmet@example.com',
            'password' => 'sifre1234',
            'password_confirmation' => 'sifre1234',
            'country_code' => 'NL',
            'preferred_currency' => 'EUR',
            'terms' => '1',
        ]);

        Notification::assertSentTo(
            User::where('email', 'mehmet@example.com')->first(),
            VerifyEmail::class
        );
    }
}
