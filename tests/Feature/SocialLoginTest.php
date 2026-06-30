<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function enableGoogle(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'http://localhost/giris/google/callback',
        ]);
    }

    protected function fakeSocialUser(string $id, ?string $email, string $name): void
    {
        $abstract = Mockery::mock(SocialiteUser::class);
        $abstract->shouldReceive('getId')->andReturn($id);
        $abstract->shouldReceive('getEmail')->andReturn($email);
        $abstract->shouldReceive('getName')->andReturn($name);
        $abstract->shouldReceive('getNickname')->andReturn(null);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($abstract);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_disabled_provider_returns_404(): void
    {
        // Yapılandırılmamış (client_id boş) → 404
        $this->get('/giris/google')->assertNotFound();
    }

    public function test_unknown_provider_returns_404(): void
    {
        config(['services.twitter.client_id' => 'x']);
        $this->get('/giris/twitter')->assertNotFound();
    }

    public function test_redirect_to_google_when_enabled(): void
    {
        $this->enableGoogle();

        $response = $this->get('/giris/google');

        $response->assertStatus(302);
        $this->assertStringContainsString('google', $response->headers->get('Location'));
    }

    public function test_callback_creates_and_logs_in_user(): void
    {
        $this->enableGoogle();
        $this->fakeSocialUser('g-12345', 'sosyal@example.com', 'Ayşe Sosyal');

        $response = $this->get('/giris/google/callback');

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'sosyal@example.com',
            'provider' => 'google',
            'provider_id' => 'g-12345',
        ]);
    }

    public function test_callback_links_to_existing_email(): void
    {
        $this->enableGoogle();
        $existing = User::factory()->create(['email' => 'mevcut@example.com', 'provider' => null]);
        $this->fakeSocialUser('g-999', 'mevcut@example.com', 'Mevcut Kullanıcı');

        $this->get('/giris/google/callback')->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['id' => $existing->id, 'provider' => 'google', 'provider_id' => 'g-999']);
    }
}
