<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * E-postaya ihtiyaç duymadan hesap kurtarma.
 *
 * Senaryo (bkz. Faz 1 · G2): sahip parolayı unutur VE e-posta (SMTP) da
 * çalışmıyordur → normal "e-posta ile sıfırla" işe yaramaz, panele kilitlenir.
 * Bu akış, önceden üretilmiş tek-kullanımlık bir kurtarma koduyla parolayı
 * doğrudan sıfırlar. Kodlar Kurtarma Kiti sayfasından üretilir.
 *
 * 2FA'yı ATLAMAZ: parolayı kurtarır; 2FA etkinse giriş yine ikinci faktör ister
 * (katmanlı savunma). Her ikisi de kaybedilirse son çare `admin:recover`.
 */
class AccountRecoveryController extends Controller
{
    public function create(): View
    {
        return view('auth.hesap-kurtar');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'max:64'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ], attributes: [
            'email' => 'e-posta',
            'code' => 'kurtarma kodu',
            'password' => 'yeni parola',
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        $recovered = $user !== null
            && $user->accountRecoveryCodesRemaining() > 0
            && $user->consumeAccountRecoveryCode($validated['code']);

        if (! $recovered) {
            // Enumeration'ı zorlaştır: e-posta var/yok, kod var/yanlış — hepsi
            // aynı genel hatayı verir. Ayrıca throttle (route) ve loglama var.
            activity('auth')
                ->withProperties(['email' => $validated['email'], 'ip' => $request->ip()])
                ->log('Hesap kurtarma denemesi başarısız');

            throw ValidationException::withMessages([
                'code' => 'E-posta veya kurtarma kodu hatalı.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60), // mevcut "beni hatırla" oturumlarını geçersiz kıl
        ])->save();

        activity('auth')
            ->performedOn($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('Hesap kurtarma koduyla parola sıfırlandı');

        return redirect()->route('login')->with(
            'status',
            'Parolan güncellendi. Yeni parolanla giriş yapabilirsin.'
        );
    }
}
