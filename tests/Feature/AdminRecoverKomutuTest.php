<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `admin:recover` — panelin son çare kapısı.
 *
 * 2FA zorunlu olduktan sonra bu komut kritikleşti: telefonunu ve yedek
 * kodlarını kaybeden tek yönetici için panele dönüşün TEK yolu bu.
 * Komutun docblock'u "2FA kaybolduğunda" diyordu ama 2FA alanlarına hiç
 * dokunmuyordu — zorunluluktan önce belge hatası, sonra kilitlenme.
 */
class AdminRecoverKomutuTest extends TestCase
{
    use RefreshDatabase;

    public function test_bayrak_verilmezse_iki_faktore_dokunmaz(): void
    {
        // Rutin parola kurtarmanın sessizce ikinci faktörü düşürmesi, kurtarma
        // komutunu bir güvenlik zaafına çevirirdi.
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'two_factor_secret' => 'GIZLIANAHTAR',
            'two_factor_confirmed_at' => now(),
        ]);

        $this->artisan('admin:recover', ['email' => $admin->email])->assertSuccessful();

        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());
    }

    public function test_bayrakla_iki_faktor_tamamen_temizlenir(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'two_factor_secret' => 'GIZLIANAHTAR',
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => json_encode(['aaaaaaaa', 'bbbbbbbb']),
        ]);

        $this->artisan('admin:recover', [
            'email' => $admin->email,
            '--iki-faktor-sifirla' => true,
        ])->assertSuccessful();

        $taze = $admin->fresh();

        $this->assertFalse($taze->hasTwoFactorEnabled());
        $this->assertNull($taze->two_factor_secret);
        $this->assertNull($taze->two_factor_confirmed_at);
        // Yedek kodlar kalsaydı, "2FA kapalı" görünen hesapta eski kodlar
        // yeniden kurulumdan sonra geçerli kalabilirdi.
        $this->assertNull($taze->two_factor_recovery_codes);
    }

    public function test_kurtarilan_hesap_panele_gerceken_donebilir(): void
    {
        // Asıl iddia bu: komut çalıştı demek yetmez, kilit gerçekten açılmalı.
        $admin = User::factory()->create([
            'role' => UserRole::Uye,
            'status' => UserStatus::Askida,
            'two_factor_secret' => 'KAYIPTELEFON',
            'two_factor_confirmed_at' => now(),
        ]);

        $this->artisan('admin:recover', [
            'email' => $admin->email,
            '--password' => 'yeni-parola-1234',
            '--iki-faktor-sifirla' => true,
        ])->assertSuccessful();

        // 2FA kapalı olduğu için giriş tek adımda tamamlanır.
        $this->post('/giris', ['email' => $admin->email, 'password' => 'yeni-parola-1234']);
        $this->assertAuthenticatedAs($admin->fresh());

        // Panel yine de 2FA kurulumuna yönlendirir — kilit açıldı, zorunluluk durdu.
        $this->get('/yonetim')->assertRedirect(route('panel.profile.2fa'));
    }

    public function test_liste_iki_faktor_durumunu_gosterir(): void
    {
        User::factory()->ikiFaktorsuz()->create(['role' => UserRole::Admin, 'email' => 'kapali@nisoya.test']);

        $this->artisan('admin:recover', ['--list' => true])
            ->expectsOutputToContain('KAPALI')
            ->assertSuccessful();
    }

    public function test_tek_yonetici_varsa_uyarir(): void
    {
        User::factory()->create(['role' => UserRole::Admin]);

        $this->artisan('admin:recover', ['--list' => true])
            ->expectsOutputToContain('Tek yönetici var')
            ->assertSuccessful();
    }
}
