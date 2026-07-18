<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

/**
 * İki faktörlü giriş challenge'ı: parola doğrulandıktan SONRA (bkz.
 * AuthenticatedSessionController::store) gösterilir. Kullanıcı henüz oturum
 * AÇMAMIŞTIR — bekleyen kimliği session'da ('login.2fa.user_id') tutulur.
 * Geçerli TOTP kodu veya tek kullanımlık yedek kod girilince tam giriş yapılır.
 */
class TwoFactorChallengeController extends Controller
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.2fa.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.iki-faktor-dogrula');
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('login.2fa.user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $user = User::findOrFail($userId);

        $usingRecovery = $request->filled('recovery_code');

        if ($usingRecovery) {
            $passed = $user->consumeRecoveryCode(trim($request->string('recovery_code')->toString()));
        } else {
            // Boşluk/tire temizle (kullanıcılar "123 456" yapıştırabiliyor).
            $code = preg_replace('/\D/', '', $request->string('code')->toString());
            $passed = $code !== '' && $this->google2fa->verifyKey($user->two_factor_secret, $code);
        }

        if (! $passed) {
            throw ValidationException::withMessages([
                $usingRecovery ? 'recovery_code' : 'code' => 'Kod geçersiz veya süresi doldu. Tekrar deneyin.',
            ]);
        }

        // Doğrulandı → tam giriş. Bekleyen session anahtarları temizlenir,
        // session ID yenilenir (session fixation koruması).
        $remember = (bool) $request->session()->pull('login.2fa.remember', false);
        $request->session()->forget('login.2fa.user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $user->forceFill(['last_seen_at' => now()])->save();

        return redirect()->intended(route('dashboard'));
    }
}
