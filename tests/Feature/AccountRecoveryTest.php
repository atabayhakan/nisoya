<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Pages\KurtarmaKiti;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Faz 1 · G2 — Kurtarma Kiti: hesap kurtarma kodları (e-postasız parola
 * sıfırlama), panel sayfası ve `admin:recover` cam-kır komutu.
 */
class AccountRecoveryTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------- Model

    public function test_generate_returns_plaintext_codes_and_stores_hashes(): void
    {
        $user = User::factory()->create();

        $codes = $user->generateAccountRecoveryCodes();

        $this->assertCount(8, $codes);
        $this->assertSame(8, $user->fresh()->accountRecoveryCodesRemaining());

        // Saklanan değerler düz metin DEĞİL (hash) olmalı.
        $stored = $user->fresh()->account_recovery_codes;
        $this->assertNotContains($codes[0], $stored);
    }

    public function test_recovery_code_is_one_time(): void
    {
        $user = User::factory()->create();
        $codes = $user->generateAccountRecoveryCodes();

        $this->assertTrue($user->consumeAccountRecoveryCode($codes[0]));
        $this->assertSame(7, $user->accountRecoveryCodesRemaining());

        // Aynı kod ikinci kez çalışmaz.
        $this->assertFalse($user->consumeAccountRecoveryCode($codes[0]));
    }

    public function test_invalid_code_is_rejected(): void
    {
        $user = User::factory()->create();
        $user->generateAccountRecoveryCodes();

        $this->assertFalse($user->consumeAccountRecoveryCode('YANLIS-KOD1'));
    }

    public function test_code_input_is_case_insensitive(): void
    {
        $user = User::factory()->create();
        $codes = $user->generateAccountRecoveryCodes();

        $this->assertTrue($user->consumeAccountRecoveryCode(strtolower($codes[0])));
    }

    // ------------------------------------------------------------ Web akışı

    public function test_recovery_page_loads(): void
    {
        $this->get('/hesap-kurtar')->assertOk()->assertSee('Hesabını kurtar');
    }

    public function test_recovery_resets_password_with_valid_code(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('eskiparola'),
        ]);
        $codes = $user->generateAccountRecoveryCodes();

        $response = $this->post('/hesap-kurtar', [
            'email' => 'admin@test.com',
            'code' => $codes[0],
            'password' => 'YeniParola123',
            'password_confirmation' => 'YeniParola123',
        ]);

        $response->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('YeniParola123', $user->password));
        $this->assertSame(7, $user->accountRecoveryCodesRemaining());
    }

    public function test_recovery_fails_with_wrong_code(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('eskiparola'),
        ]);
        $user->generateAccountRecoveryCodes();

        $this->post('/hesap-kurtar', [
            'email' => 'admin@test.com',
            'code' => 'BOZUK-KODX',
            'password' => 'YeniParola123',
            'password_confirmation' => 'YeniParola123',
        ])->assertSessionHasErrors('code');

        $this->assertTrue(Hash::check('eskiparola', $user->fresh()->password));
    }

    public function test_recovery_fails_for_unknown_email(): void
    {
        $this->post('/hesap-kurtar', [
            'email' => 'yok@test.com',
            'code' => 'HERHANGI-1',
            'password' => 'YeniParola123',
            'password_confirmation' => 'YeniParola123',
        ])->assertSessionHasErrors('code');
    }

    public function test_recovery_code_cannot_be_reused_via_http(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('eskiparola'),
        ]);
        $codes = $user->generateAccountRecoveryCodes();

        $this->post('/hesap-kurtar', [
            'email' => 'admin@test.com',
            'code' => $codes[0],
            'password' => 'YeniParola123',
            'password_confirmation' => 'YeniParola123',
        ])->assertRedirect(route('login'));

        // Aynı kodla ikinci deneme başarısız.
        $this->post('/hesap-kurtar', [
            'email' => 'admin@test.com',
            'code' => $codes[0],
            'password' => 'BaskaParola456',
            'password_confirmation' => 'BaskaParola456',
        ])->assertSessionHasErrors('code');

        $this->assertTrue(Hash::check('YeniParola123', $user->fresh()->password));
    }

    // --------------------------------------------------------------- Panel

    public function test_admin_can_view_recovery_kit(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);

        $this->actingAs($admin)
            ->get('/yonetim/kurtarma-kiti')
            ->assertOk()
            ->assertSee('Kurtarma Kiti');
    }

    public function test_member_redirected_from_recovery_kit(): void
    {
        $user = User::factory()->create(['role' => UserRole::Uye, 'email_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/yonetim/kurtarma-kiti')
            ->assertRedirect(route('dashboard'));
    }

    public function test_moderator_forbidden_from_recovery_kit(): void
    {
        $mod = User::factory()->create(['role' => UserRole::Moderator, 'email_verified_at' => now()]);

        $this->actingAs($mod)
            ->get('/yonetim/kurtarma-kiti')
            ->assertForbidden();
    }

    public function test_generate_codes_action_creates_codes(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
        $this->actingAs($admin);

        $component = Livewire::test(KurtarmaKiti::class)->call('generateCodes');

        $this->assertCount(8, $component->get('generatedCodes'));
        $this->assertSame(8, $admin->fresh()->accountRecoveryCodesRemaining());
    }

    // ------------------------------------------------------------- Komut

    public function test_admin_recover_command_resets_password_and_promotes(): void
    {
        $user = User::factory()->create([
            'email' => 'x@test.com',
            'role' => UserRole::Uye,
            'status' => UserStatus::Askida,
            'password' => Hash::make('eski'),
        ]);

        $this->artisan('admin:recover', ['email' => 'x@test.com', '--password' => 'YeniParola123'])
            ->assertExitCode(0);

        $user->refresh();
        $this->assertTrue(Hash::check('YeniParola123', $user->password));
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertSame(UserStatus::Aktif, $user->status);
    }

    public function test_admin_recover_unknown_email_fails(): void
    {
        $this->artisan('admin:recover', ['email' => 'yok@test.com'])->assertExitCode(1);
    }

    public function test_admin_recover_list_runs(): void
    {
        User::factory()->create(['role' => UserRole::Admin]);

        $this->artisan('admin:recover', ['--list' => true])->assertExitCode(0);
    }
}
