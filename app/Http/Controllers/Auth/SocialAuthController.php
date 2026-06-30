<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    private const PROVIDERS = ['google', 'facebook'];

    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureEnabled($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->ensureEnabled($provider);

        try {
            $social = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Sosyal giriş tamamlanamadı, lütfen tekrar dene.']);
        }

        $user = User::query()->where('provider', $provider)->where('provider_id', $social->getId())->first();

        // E-posta eşleşiyorsa mevcut hesaba bağla
        if (! $user && $social->getEmail()) {
            $user = User::query()->where('email', $social->getEmail())->first();
            $user?->update(['provider' => $provider, 'provider_id' => $social->getId()]);
        }

        // Yoksa yeni hesap oluştur
        if (! $user) {
            $name = $social->getName() ?: ($social->getNickname() ?: 'Üye');
            $user = User::create([
                'name' => $name,
                'username' => $this->uniqueUsername($name),
                'email' => $social->getEmail() ?: ($provider.'_'.$social->getId().'@nisoya.local'),
                'email_verified_at' => now(), // OAuth e-postası doğrulanmış sayılır
                'provider' => $provider,
                'provider_id' => $social->getId(),
                'preferred_currency' => 'EUR',
            ]);
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }

    private function ensureEnabled(string $provider): void
    {
        abort_unless(
            in_array($provider, self::PROVIDERS, true) && config("services.{$provider}.client_id"),
            404,
        );
    }

    private function uniqueUsername(string $name): string
    {
        $base = Str::slug($name) ?: 'uye';
        $username = $base;
        $i = 1;

        while (User::query()->where('username', $username)->exists()) {
            $i++;
            $username = $base.'-'.$i;
        }

        return $username;
    }
}
