<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => Str::slug(fake()->unique()->userName()).'-'.fake()->unique()->numberBetween(1000, 999999),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'country_code' => 'DE',
            'preferred_currency' => 'EUR',
        ];
    }

    /**
     * Yönetici rolündeki test kullanıcıları 2FA'lı doğar (2026-08-05).
     *
     * -----------------------------------------------------------------------
     * NEDEN FABRİKADA, TESTLERDE DEĞİL
     *
     * `YonetimIkiFaktorZorunlu` devreye girince, yönetim panelini kullanan ~40
     * test dosyasındaki admin'ler 2FA kurulum sayfasına yönlendirilmeye başladı
     * (37 kırık, hepsi aynı sebep). O testlerin derdi 2FA değil — panelde bir
     * ekranın açıldığını doğruluyorlar.
     *
     * Doğru düzeltme, her dosyaya elle 2FA alanı eklemek değil; ÜRETİMDEKİ
     * GERÇEĞİ fabrikaya yansıtmak: artık "yönetici" demek "2FA'sı olan kişi"
     * demek. Bu tanım değişince testler kendiliğinden gerçeğe uyar.
     *
     * `role` `definition()` çalıştıktan SONRA birleştiği için bu karar burada
     * verilemez; `afterMaking` son hâli görebilen tek yer.
     *
     * Tersini isteyen testler `ikiFaktorsuz()` durumunu kullanır — o durumun
     * kancası sonra kaydedildiği için bunu ezer (bkz. YonetimGirisiTest).
     */
    public function configure(): static
    {
        return $this->afterMaking(function (User $user): void {
            if ($user->role === UserRole::Admin && $user->two_factor_secret === null) {
                $user->forceFill([
                    'two_factor_secret' => 'FABRIKAGIZLIANAHTARI',
                    'two_factor_confirmed_at' => now(),
                ]);
            }
        });
    }

    /**
     * İki faktörlü doğrulaması KURULMAMIŞ kullanıcı.
     *
     * Yönetici için anlamlıdır: zorunluluk middleware'inin yönlendirmesini ve
     * kurtarma akışlarını test etmenin tek yolu budur.
     */
    public function ikiFaktorsuz(): static
    {
        return $this->afterMaking(function (User $user): void {
            $user->forceFill([
                'two_factor_secret' => null,
                'two_factor_confirmed_at' => null,
                'two_factor_recovery_codes' => null,
            ]);
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
