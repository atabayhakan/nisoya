<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

/**
 * Kullanıcı için İki Faktörlü Kimlik Doğrulama (TOTP — Google Authenticator uyumlu).
 * Akış: enable (QR üret) → confirm (kod doğrula) → aktif.
 * Disable: mevcut şifre + bir kez OTP kodu ister.
 */
class TwoFactorController extends Controller
{
    public function __construct(private readonly Google2FA $google2fa) {}

    /**
     * GET /panel/profil/iki-faktor — 2FA yönetim sayfası.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $enabled = $user->two_factor_confirmed_at !== null;

        return view('panel.profile.two-factor', [
            'user' => $user,
            'enabled' => $enabled,
        ]);
    }

    /**
     * POST /panel/profil/iki-faktor/setup — 2FA secret üret, QR kod göster.
     * Henüz confirmed değilse secret geçici olarak DB'ye yazılır (henüz aktif değil).
     */
    public function setup(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->two_factor_confirmed_at) {
            return back()->withErrors(['two_factor' => 'İki faktörlü kimlik doğrulama zaten etkin.']);
        }

        $secret = $this->google2fa->generateSecretKey();
        $user->update(['two_factor_secret' => $secret]); // henüz confirmed_at yok

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name', 'Nisoya'),
            $user->email,
            $secret
        );

        return redirect()->route('panel.profile.2fa')
            ->with('qr_code_url', $qrCodeUrl)
            ->with('secret', $secret);
    }

    /**
     * POST /panel/profil/iki-faktor/confirm — 6 haneli kodu doğrula, etkinleştir.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], attributes: ['code' => 'doğrulama kodu']);

        $user = $request->user();
        $secret = $user->two_factor_secret;

        if (! $secret) {
            throw ValidationException::withMessages(['code' => 'Önce kurulum yapmalısın.']);
        }

        $valid = $this->google2fa->verifyKey($secret, $request->string('code')->toString());

        if (! $valid) {
            throw ValidationException::withMessages(['code' => 'Doğrulama kodu geçersiz. Tekrar deneyin.']);
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => json_encode($recoveryCodes),
        ]);

        return redirect()->route('panel.profile.2fa')
            ->with('status', 'İki faktörlü kimlik doğrulama etkinleştirildi.')
            ->with('recovery_codes', $recoveryCodes);
    }

    /**
     * POST /panel/profil/iki-faktor/disable — 2FA kapat (mevcut şifre + OTP).
     */
    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'code' => ['required', 'string', 'size:6'],
        ], attributes: [
            'current_password' => 'mevcut şifre',
            'code' => 'doğrulama kodu',
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            throw ValidationException::withMessages(['two_factor' => 'İki faktörlü kimlik doğrulama zaten etkin değil.']);
        }

        $valid = $this->google2fa->verifyKey($user->two_factor_secret, $request->string('code')->toString());

        if (! $valid) {
            throw ValidationException::withMessages(['code' => 'Doğrulama kodu geçersiz.']);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        return redirect()->route('panel.profile.2fa')
            ->with('status', 'İki faktörlü kimlik doğrulama devre dışı bırakıldı.');
    }

    /**
     * POST /panel/profil/iki-faktor/recovery — Yedek kodlardan birini kullan.
     * (Login akışında kullanılır; bu metot login controller'ından çağrılır)
     */
    public function useRecoveryCode(Request $request, User $user, string $code): bool
    {
        $recoveryCodes = json_decode($user->two_factor_recovery_codes ?? '[]', true) ?: [];
        $key = array_search($code, $recoveryCodes, true);

        if ($key === false) {
            return false;
        }

        // Kullanılan kodu listeden çıkar
        unset($recoveryCodes[$key]);
        $user->update(['two_factor_recovery_codes' => json_encode(array_values($recoveryCodes))]);

        return true;
    }

    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        }

        return $codes;
    }
}
